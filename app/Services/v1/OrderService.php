<?php

namespace App\Services\v1;

use App\Events\ExecutorRatedEvent;
use App\Events\OfferAcceptedEvent;
use App\Events\OfferCreatedEvent;
use App\Http\Requests\Order\CommentOrderRequest;
use App\Models\Chat;
use App\Models\Executor;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\User;
use App\Presenters\v1\ExecutorPresenter;
use App\Presenters\v1\OfferPresenter;
use App\Presenters\v1\OrderPresenter;
use App\Repositories\CommentRepo;
use App\Repositories\ExecutorRepo;
use App\Repositories\OrderOfferRepo;
use App\Repositories\OrderRepo;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;

class OrderService extends BaseService
{
    private OrderRepo $orderRepo;

    public function __construct() {
        $this->orderRepo = new OrderRepo();
    }

    public function create(User $user, $data)
    {
        $data['user_id'] = $user->id;
        $data['executor_id'] = 0;
        $data['status'] = Order::STATUS_MODERATE;
        $order = $this->orderRepo->store($data);

        if (isset($data['files'])) {
            foreach($data['files'] as $file) {
                $path = $file->store('public/order/');
                $order->media()->create([
                    'storage_link' => Storage::url($path), 
                ]);
            }
        }

        return $this->result([
            'order' => (new OrderPresenter($order))->detail(),
        ]);
    }

    public function createOffer(User $user, array $data, int $orderId)
    {
        $offerRepo = new OrderOfferRepo();

        $offer = $offerRepo->getByUserIdAndOrderId($orderId, $user->id);

        if (!is_null($offer)) {
            return $this->error(406, __('order.already_offered'));
        }

        $data['order_id'] = $orderId;
        $data['user_id'] = $user->id;
        $data['status'] = Order::STATUS_ACTIVE;

        $offerRepo->store($data);

        $order = Order::find($orderId);
        event(new OfferCreatedEvent($order));

        return $this->ok(__('order.offer_sent'));
    }

    public function update(int $id, array $data)
    {
        $order = Order::find($id);

        if (is_null($order)) {
            return $this->errNotFound(__('order.not_found'));
        }

        $user = auth('api')->user();

        if ($order->user_id != $user->id) {
            return $this->error(403, __('order.cannot_edit_foreign'));
        }
        $order->media()->delete();

        if (isset($data['files'])) {
            foreach($data['files'] as $file) {
                $path = $file->store('public/order/');
                $order->media()->create([
                    'storage_link' => Storage::url($path), 
                ]);
            }
        }
        unset($data['files']);

        $this->orderRepo->update($order, $data);

        return $this->ok(__('order.saved'));
    }

    public function index($params)
    {
        // В общем списке заказов не показываем заказы самого пользователя.
        // Для гостя (нет токена) фильтр не применяется — видны все заказы.
        $user = $this->apiAuthUser();
        if ($user) {
            $params['exclude_user_id'] = $user->id;
        }

        // Заказчик удалил аккаунт — заказ уходит из общей выдачи (и для гостя тоже).
        $params['exclude_deleted_users'] = true;

        $orders = $this->orderRepo->index($params);

        return $this->resultCollections($orders, OrderPresenter::class, 'list');
    }

    public function indexMy(array $params)
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('order.auth_error'));
        }
        $params['user_id'] = $user->id;
        $orders = $this->orderRepo->index($params);
        return $this->resultCollections($orders, OrderPresenter::class, 'list');
    }

    public function indexMyResponded(array $params)
    {
        $executor = $this->apiAuthUser()->executor()->first();
        if (is_null($executor)) {
            return $this->errNotFound(__('order.executor_not_found'));
        }
        $params['executor_id'] = $executor->id;
        $orders = $this->orderRepo->index($params);
        return $this->resultCollections($orders, OrderPresenter::class, 'list');
    }

    public function info($id)
    {
        $order = Order::with('media', 'category', 'executor.user')->find($id);
        if (is_null($order)) {
            return $this->errNotFound(__('order.not_found'));
        }

        $user = $this->apiAuthUser();
        $isResponded = false;
        if (!is_null($user)) {
            $offer = OrderOffer::where('user_id', $user->id)->where('order_id', $id)->first();
            if ($offer) {
                $isResponded = true;
            }
        }

        return $this->result([
            'order' => (new OrderPresenter($order))->detail(),
            'is_responded' => $isResponded,
        ]);
    }

    public function getOffers($orderId): array
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->error(401, __('order.unauthorized'));
        }
        $order = Order::find($orderId);
        if (is_null($order)) {
            return $this->errNotFound(__('order.not_found'));
        }

        if ($order->user_id != $user->id) {
            return $this->error(403, __('order.offers_no_access'));
        }

        $offerRepo = new OrderOfferRepo();

        return $this->resultCollections($offerRepo->getByOrderId($orderId), OfferPresenter::class, 'list');
    }

    public function infoOffer($orderId, $offerId)
    {
        $order = Order::find($orderId);
        if (is_null($order)) {
            return $this->errNotFound(__('order.not_found'));
        }

        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('order.unauthorized'));
        }

        if ($order->user_id != $user->id) {
            return $this->error(403, __('order.offers_no_access'));
        }

        $offerRepo = new OrderOfferRepo();
        $offer = $offerRepo->info($offerId);
        
        return $this->result([
            'offer' => (new OfferPresenter($offer))->info(),
        ]);
    }

    public function delete(int $orderId)
    {
        $order = Order::find($orderId);
        if (is_null($order)) {
            return $this->errNotFound(__('order.not_found'));
        }

        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('order.unauthorized'));
        }

        if ($order->user_id != $user->id) {
            return $this->error(406, __('order.cannot_delete_foreign'));
        }

        $this->orderRepo->update($order, ['status' => Order::STATUS_ARCHIVE]);

        return $this->ok();
    }

    public function complete(int $orderId)
    {
        $order = Order::find($orderId);
        if (is_null($order)) {
            return $this->errNotFound(__('order.not_found'));
        }

        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('order.unauthorized'));
        }

        if ($order->user_id != $user->id) {
            return $this->error(406, __('order.cannot_complete_foreign'));
        }

        if ($order->status != Order::STATUS_HAS_EXECUTOR) {
            return $this->error(406, __('order.must_be_in_progress'));
        }

        $this->orderRepo->update($order, ['status' => Order::STATUS_COMPLETED]);

        return $this->ok();
    }

    public function accept(int $orderId, int $offerId)
    {
        $order = Order::find($orderId);
        if (is_null($order)) {
            return $this->errNotFound(__('order.not_found'));
        }

        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('order.unauthorized'));
        }
        
        $offer = OrderOffer::find($offerId);
        if (is_null($offer)) {
            return $this->errNotFound(__('order.offer_not_found'));
        }

        if ($order->user_id != $user->id) {
            return $this->error(406, __('order.cannot_accept_foreign_offer'));
        }

        // Отклик остаётся видимым после удаления аккаунта автора, но назначить
        // такого исполнителя нельзя — заказ получил бы «мёртвого» исполнителя.
        if (is_null($offer->user) || $offer->user->trashed()) {
            return $this->error(406, __('order.offer_author_deleted'));
        }

        $executor = Executor::where('user_id', $offer->user_id)->first();
        if (is_null($executor)) {
            return $this->errNotFound(__('order.executor_not_found'));
        }

        $this->orderRepo->update($order, [
            'status' => Order::STATUS_HAS_EXECUTOR,
            'executor_id' => $executor->id,
        ]);

        event(new OfferAcceptedEvent($offer->user_id, $orderId));

        return $this->ok();
    }

    public function rateExecutor(int $orderId, array $data)
    {
        $order = Order::find($orderId);
        if (is_null($order)) {
            return $this->errNotFound(__('order.not_found'));
        }

        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errUnauthenticated(__('order.unauthorized'));
        }

        if ($order->user_id != $user->id) {
            return $this->error(406, __('order.cannot_rate_foreign'));
        }

        if ($order->status != Order::STATUS_COMPLETED) {
            return $this->error(406, __('order.cannot_rate_yet'));
        }

        $executor = Executor::find($order->executor_id);
        if (is_null($executor)) {
            return $this->errNotFound(__('order.executor_not_found'));
        }

        $rating = $executor->ratings()->create([
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

        event(new ExecutorRatedEvent($executor));

        return $this->ok();
    }
}