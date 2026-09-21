<?php

namespace App\Services\v1;

use App\Events\ExecutorRatedEvent;
use App\Events\OfferAcceptedEvent;
use App\Events\OfferCreatedEvent;
use App\Events\OrderArchivedEvent;
use App\Events\OrderCompletedEvent;
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
use App\Repositories\OrderViewRepo;
use App\Services\BaseService;
use Illuminate\Support\Facades\Storage;

class OrderService extends BaseService
{
    private OrderRepo $orderRepo;
    private OrderViewRepo $orderViewRepo;

    public function __construct() {
        $this->orderRepo = new OrderRepo();
        $this->orderViewRepo = new OrderViewRepo();
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

        // Автор только что видел свой заказ — с этого состояния и считаем
        // изменения. Без отметки первая же смена статуса на модерации не дала
        // бы бейджа: правило «нет строки — нового нет».
        $this->orderViewRepo->markSeen($user->id, $order);

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

        // С этого момента заказ показывается исполнителю во вкладке «как
        // исполнитель», поэтому заводим строку просмотра. Без неё
        // countRespondedWithUpdates (INNER JOIN по order_views) заказ не
        // увидит, и назначение не зажжёт бейдж.
        $this->orderViewRepo->markSeen($user->id, $order);

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
        $params['viewer_id'] = $user->id;
        $orders = $this->orderRepo->index($params);
        return $this->resultCollections($orders, OrderPresenter::class, 'list');
    }

    public function indexMyResponded(array $params)
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errFobidden(__('order.auth_error'));
        }

        $executor = $user->executor()->first();
        if (is_null($executor)) {
            return $this->errNotFound(__('order.executor_not_found'));
        }
        $params['executor_id'] = $executor->id;
        // Пара с executor_id включает набор вкладки целиком: назначенные мне
        // и отклики, по которым заказчик ещё не решил.
        $params['responded_by_user_id'] = $user->id;
        $params['viewer_id'] = $user->id;
        $orders = $this->orderRepo->index($params);
        return $this->resultCollections($orders, OrderPresenter::class, 'list');
    }

    // Счётчики для бейджей в меню и на вкладках «моих заказов». Отдельный
    // лёгкий эндпоинт: опрашивать ради двух чисел полные списки заказов
    // с описаниями незачем.
    public function badges()
    {
        $user = $this->apiAuthUser();
        if (is_null($user)) {
            return $this->errFobidden(__('order.auth_error'));
        }

        $executor = $user->executor()->first();

        return $this->result([
            'badges' => [
                'my' => $this->orderViewRepo->countMyWithUpdates($user->id),
                'responded' => is_null($executor)
                    ? 0
                    : $this->orderViewRepo->countRespondedWithUpdates($user->id, $executor->id),
            ],
        ]);
    }

    public function info($id)
    {
        $order = Order::with('media', 'category', 'executor.user')->find($id);
        if (is_null($order)) {
            return $this->errNotFound(__('order.not_found'));
        }

        $user = $this->apiAuthUser();
        $isResponded = false;
        $myOfferId = null;
        if (!is_null($user)) {
            $offer = OrderOffer::where('user_id', $user->id)->where('order_id', $id)->first();
            if ($offer) {
                $isResponded = true;
                $myOfferId = $offer->id;
            }

            // Карточку открыли — бейдж на ней гаснет. Отметку ставим только
            // тем, кому этот заказ вообще показывается в «моих»: заказчику и
            // назначенному исполнителю. Случайный зритель из общей ленты
            // строк в order_views не плодит.
            if ($order->user_id == $user->id
                || $this->isAssignedExecutor($order, $user)
                || $isResponded) {
                $this->orderViewRepo->markSeen($user->id, $order);
            }
        }

        return $this->result([
            'order' => (new OrderPresenter($order))->detail(),
            'is_responded' => $isResponded,
            // Свой отклик смотрящего: по нему приложение вместо «Предложить
            // услуги» показывает «Посмотреть предложение». Второй отклик
            // createOffer всё равно отклоняет (406 already_offered).
            'my_offer_id' => $myOfferId,
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

        $offerRepo = new OrderOfferRepo();
        $offer = $offerRepo->info($offerId);

        // Отклик должен относиться к этому заказу. Раньше проверки не было,
        // и через адрес своего заказа открывался любой отклик по id.
        if (is_null($offer) || $offer->order_id != $order->id) {
            return $this->errNotFound(__('order.not_found'));
        }

        // Смотреть отклик может заказчик — и автор отклика, свой и только
        // для чтения (кнопка «Посмотреть предложение» на карточке заказа).
        if ($order->user_id != $user->id && $offer->user_id != $user->id) {
            return $this->error(403, __('order.offers_no_access'));
        }

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

        // См. комментарий в complete(): mass update не поднимает события модели.
        event(new OrderArchivedEvent($order));

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

        // Событие поднимаем здесь, а не в OrderObserver: OrderRepo::update()
        // меняет статус через query builder (Order::where(...)->update()), а
        // он модельные события не вызывает.
        event(new OrderCompletedEvent($order));

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

        // Заказ только что появился у исполнителя во вкладке «мои отклики».
        // Отмечаем текущее состояние, чтобы бейдж дал следующая смена статуса,
        // а не сам факт назначения.
        $this->orderViewRepo->markSeen($offer->user_id, $order->fresh());

        event(new OfferAcceptedEvent($offer->user_id, $orderId));

        return $this->ok();
    }

    // Пользователь — назначенный исполнитель этого заказа (именно такие заказы
    // отдаёт вкладка «мои отклики»).
    private function isAssignedExecutor(Order $order, User $user): bool
    {
        if (empty($order->executor_id)) {
            return false;
        }

        $executor = $user->executor()->first();

        return !is_null($executor) && $executor->id == $order->executor_id;
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