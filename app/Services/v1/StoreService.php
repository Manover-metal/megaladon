<?php

namespace App\Services\v1;

use App\Events\StoreRatedEvent;
use App\Models\Store;
use App\Models\User;
use App\Presenters\v1\CompanyTypePresenter;
use App\Presenters\v1\RatingPresenter;
use App\Presenters\v1\StorePresenter;
use App\Repositories\CompanyRepo;
use App\Repositories\StoreRepo;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;

class StoreService extends BaseService
{
    private StoreRepo $storeRepo;

    public function __construct() {
        $this->storeRepo = new StoreRepo();
    }

    public function types()
    {
        $collections = (new CompanyRepo())->index();
        return $this->resultCollections($collections, CompanyTypePresenter::class, 'list');
    }

    public function updateProfile(User $user, array $data) : array
    {
        $store = $this->storeRepo->getByUserId($user->id);
        if (is_null($store)) {
            return $this->errNotFound(__('store.not_found'));
        }

        if (isset($data['contacts'])) {
            $store->contacts()->delete();
            foreach ($data['contacts'] as $contactData) {
                $contactData['store_id'] = $store->id;
                $store->contacts()->create($contactData);
            }
        }
        unset($data['contacts']);

        $this->storeRepo->update($store->id, $data);

        return $this->ok(__('store.updated'));
    }

    public function index(array $params)
    {
        // В списке металлопроката не показываем магазин самого пользователя.
        // Для гостя (нет токена) фильтр не применяется — видны все магазины.
        $user = $this->apiAuthUser();
        if ($user) {
            $params['exclude_user_id'] = $user->id;
        }

        $stores = $this->storeRepo->index($params);
        $count = $this->storeRepo->count($params);
        return $this->resultCollections($stores, StorePresenter::class, 'list', $count);
    }

    public function info($id)
    {
        $store = Store::with('contacts', 'media', 'user')->find($id);

        // Магазин без активной подписки скрыт: отдаём тот же 404, что и для
        // несуществующего, чтобы не раскрывать факт просроченной подписки.
        if (is_null($store) || is_null($store->activeInvoice())) {
            return $this->errNotFound(__('store.not_found'));
        }

        return $this->result(['store' => (new StorePresenter($store))->detail()]);
    }

    public function ratings($id)
    {
        $store = Store::find($id);

        // Скрываем магазин без подписки от всех, кроме владельца: он открывает
        // отзывы своего магазина из профиля и должен видеть их и тогда, когда
        // подписка кончилась. Раньше и ему приходило «магазин не найден».
        $user = $this->apiAuthUser();
        $isOwner = $store && $user && (int) $store->user_id === (int) $user->id;

        if (is_null($store) || (!$isOwner && is_null($store->activeInvoice()))) {
            return $this->errNotFound(__('store.not_found'));
        }

        $ratings = $store->ratings()->with(['media', 'user'])->latest()->get();

        return $this->resultCollections($ratings, RatingPresenter::class, 'list');
    }

    public function uploadPriceList(array $data)
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('store.unauthorized'));
        }

        $store = $this->storeRepo->getByUserId($user->id);
        if (is_null($store)) {
            return $this->errNotFound(__('store.not_found'));
        }

        if ($store->media()->where('active', 1)->count() >= 2) {
            return $this->error(406, __('store.price_upload_limit', ['limit' => 2]));
        }

        $path = $data['file']->store('public/store/');
        $store->media()->create([
            'storage_link' => Storage::url($path), 
        ]);

        return $this->ok();
    }

    public function activatePrice(int $id)
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('store.unauthorized'));
        }

        $store = $this->storeRepo->getByUserId($user->id);
        if (is_null($store)) {
            return $this->errNotFound(__('store.not_found'));
        }

        $file = $store->media()->where('id', $id)->first();
        if (is_null($file)) {
            return $this->errNotFound(__('store.price_not_found'));
        }
		
		if ($store->media()->where('active', 1)->count() >= 2) {
			return $this->errNotAcceptable(__('store.price_activate_limit', ['limit' => 2]));
		}

        $store->media()->where('id', $id)->update(['active' => 1]);

        return $this->ok();
    }

    public function deactivatePrice(int $id)
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('store.unauthorized'));
        }

        $store = $this->storeRepo->getByUserId($user->id);
        if (is_null($store)) {
            return $this->errNotFound(__('store.not_found'));
        }

        $file = $store->media()->where('id', $id)->first();
        if (is_null($file)) {
            return $this->errNotFound(__('store.price_not_found'));
        }

        $store->media()->where('id', $id)->update(['active' => 0]);

        return $this->ok();
    }

    public function deletePrice(int $id)
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('store.unauthorized'));
        }

        $store = $this->storeRepo->getByUserId($user->id);
        if (is_null($store)) {
            return $this->errNotFound(__('store.not_found'));
        }

        $file = $store->media()->where('id', $id)->first();
        if (is_null($file)) {
            return $this->errNotFound(__('store.price_not_found'));
        }

        $store->media()->where('id', $id)->delete();

        return $this->ok();
    }

    public function rateStore(int $storeId, array $data)
    {
        $store = Store::find($storeId);
        if (is_null($store) || is_null($store->activeInvoice())) {
            return $this->errNotFound(__('store.not_found'));
        }

        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated('Требуется авторизация');
        }

        if ($store->user_id == $user->id) {
            return $this->error(406, __('store.cannot_rate_own'));
        }

        // Один пользователь может оценить магазин только один раз.
        if ($store->ratings()->where('user_id', $user->id)->exists()) {
            return $this->error(409, __('store.already_rated'));
        }

        $rating = $store->ratings()->create([
            'user_id' => $user->id,
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        if (isset($data['images'])) {
            foreach ($data['images'] as $image) {
                $path = $image->store('public/rating');
                $rating->media()->create([
                    'storage_link' => Storage::url($path),
                ]);
            }
        }

        event(new StoreRatedEvent($store));

        return $this->ok();
    }

    public function updateRating(Store $store) : void
    {
        $ratings = $store->ratings()->get();
        $countRates = $ratings->count();

        // Отзывов может не остаться — например, админ удалил последний.
        // Без этой ветки деление ниже роняет запрос DivisionByZeroError.
        if ($countRates === 0) {
            $this->storeRepo->update($store->id, ['rating' => 0]);

            return;
        }

        $sumRating = 0;
        foreach ($ratings as $rating) {
            $sumRating += $rating->rate;
        }

        $this->storeRepo->update($store->id, ['rating' => round($sumRating / $countRates, 1)]);
    }
}