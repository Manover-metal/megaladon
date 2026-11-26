<?php

namespace App\Presenters\v1;

use App\Models\UserBlock;
use App\Presenters\BasePresenter;

class UserPresenter extends BasePresenter
{
    public function profile()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo_url' => $this->photo_url ? url($this->photo_url) : null,
            'phone' => $this->phone,
            'city' => $this->city ? (new CityPresenter($this->city))->list() : null,
            'executor' => $this->executor ? (new ExecutorPresenter($this->executor))->edited() : null,
            'store' => $this->store ? (new StorePresenter($this->store))->edited() : null,
        ];
    }

    /**
     * Карточка для публичной страницы пользователя. В отличие от profile()
     * не отдаёт вложенные executor и store — страница про человека, а не
     * про его роли, и эндпоинт открыт без авторизации.
     */
    public function publicProfile()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo_url' => $this->photo_url ? url($this->photo_url) : null,
            'phone' => $this->phone,
            'city' => $this->city ? (new CityPresenter($this->city))->list() : null,
            'count_orders' => $this->countCompletedOrders(),
            'created_at' => $this->created_at,
            // Сюда попадают только неудалённые (иначе 404), но поле оставляем
            // для единообразия с short()/shortAdvert(): клиент разбирает все
            // три формы одной моделью UserModel.
            'is_deleted' => false,
        ] + $this->blockFlags();
    }

    public function short()
    {
        if ($this->isDeleted()) {
            return $this->deleted();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'photo_url' => $this->photo_url ? url($this->photo_url) : null,
            'count_orders' => $this->countCompletedOrders(),
            'is_deleted' => false,
            // 'rating' => $this->rating,
        ] + $this->blockFlags();
    }

    public function shortAdvert()
    {
        if ($this->isDeleted()) {
            return $this->deleted();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            // Телефон закрыт, пока между людьми блокировка в любую сторону.
            'phone' => $this->blockedEitherWay() ? null : $this->phone,
            'photo_url' => $this->photo_url ? url($this->photo_url) : null,
            'count_orders' => $this->countCompletedOrders(),
            'is_deleted' => false,
            // 'rating' => $this->rating,
        ] + $this->blockFlags();
    }

    /**
     * Приложение гасит «Написать», «Позвонить» и отклик заранее, не дожидаясь
     * 403 от сервера, поэтому флаги едут вместе с пользователем.
     */
    private function blockFlags(): array
    {
        $viewerId = auth('api')->id();
        return [
            'blocked_by_me' => UserBlock::blockedByMe($viewerId, (int) $this->id),
            'blocked_me' => UserBlock::blockedMe($viewerId, (int) $this->id),
        ];
    }

    private function blockedEitherWay(): bool
    {
        return UserBlock::between(auth('api')->id(), (int) $this->id);
    }

    // Аккаунт удалён (soft delete), но его отклики, объявления и чаты остаются.
    // Отдаём обезличенную заглушку: без телефона, фото и счётчика заказов.
    private function deleted(): array
    {
        return [
            'id' => $this->id,
            'name' => __('user.deleted_account_name'),
            'phone' => null,
            'photo_url' => null,
            'count_orders' => null,
            'is_deleted' => true,
        ];
    }

    private function isDeleted(): bool
    {
        return $this->model !== null && $this->model->trashed();
    }
}
