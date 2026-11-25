<?php

namespace App\Services\v1;

use App\Models\Service;
use App\Presenters\v1\ServicePresenter;
use App\Repositories\ServiceRepo;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;

/** Услуги исполнителей и магазинов: публичная лента, свои, карточка, CRUD. */
class ServiceService extends BaseService
{
    private ServiceRepo $repo;

    public function __construct()
    {
        $this->repo = new ServiceRepo();
    }

    public function index(array $params)
    {
        return $this->resultCollections($this->repo->index($params), ServicePresenter::class, 'list');
    }

    public function info($id)
    {
        $service = $this->repo->info($id);

        if (is_null($service)) {
            return $this->errNotFound(__('service.not_found'));
        }

        // Непрошедшую модерацию услугу видит только автор.
        if ($service->status != Service::STATUS_ACTIVE && $service->user_id != auth('api')->id()) {
            return $this->errNotFound(__('service.not_found'));
        }

        return $this->result([
            'service' => (new ServicePresenter($service))->info(),
        ]);
    }

    public function create(array $data)
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated('Unathorized');
        }

        $checkAuthor = $this->checkAuthor($user);
        if (!$this->isSuccess($checkAuthor)) {
            return $checkAuthor;
        }

        $data['user_id'] = $user->id;
        $data['status'] = Service::STATUS_MODERATE;
        $files = $data['files'] ?? [];
        $categories = $data['category_ids'];
        unset($data['files'], $data['category_ids']);

        $service = $this->repo->store($data);
        $service->categories()->sync($categories);
        $this->attachFiles($service, $files);

        return $this->ok(__('service.created'));
    }

    public function update($id, array $data)
    {
        $service = Service::find($id);

        if (is_null($service)) {
            return $this->errNotFound(__('service.not_found'));
        }

        $user = auth('api')->user();

        if ($service->user_id != $user->id) {
            return $this->error(403, __('service.cannot_edit_foreign'));
        }

        $checkAuthor = $this->checkAuthor($user);
        if (!$this->isSuccess($checkAuthor)) {
            return $checkAuthor;
        }

        $service->media()->delete();
        $this->attachFiles($service, $data['files'] ?? []);
        if (isset($data['category_ids'])) {
            $service->categories()->sync($data['category_ids']);
        }
        unset($data['files'], $data['category_ids']);

        // После правок услуга снова уходит на проверку.
        $data['status'] = Service::STATUS_MODERATE;
        $data['reject_reason'] = null;

        $this->repo->update($service->id, $data);

        return $this->ok(__('service.saved'));
    }

    public function delete($id)
    {
        $service = Service::find($id);

        if (is_null($service)) {
            return $this->errNotFound(__('service.not_found'));
        }

        if ($service->user_id != auth('api')->id()) {
            return $this->error(403, __('service.cannot_delete_foreign'));
        }

        $service->media()->delete();
        $service->delete();

        return $this->ok(__('service.deleted'));
    }

    private function attachFiles(Service $service, array $files): void
    {
        foreach ($files as $file) {
            $path = $file->store('public/service/');
            $service->media()->create(['storage_link' => Storage::url($path)]);
        }
    }

    // Услуги выставляют исполнители и магазины, и только с оплаченной
    // подпиской. Если есть оба профиля — хватает подписки любого из них.
    private function checkAuthor($user)
    {
        $executor = $user->executor()->first();
        $store = $user->store()->first();

        if (is_null($executor) && is_null($store)) {
            return $this->errFobidden(__('service.forbidden'));
        }
        if (($executor && $executor->hasAccess()) || ($store && $store->hasAccess())) {
            return $this->ok();
        }

        return $this->errPaymentRequired(__('executor.no_subscription'));
    }
}
