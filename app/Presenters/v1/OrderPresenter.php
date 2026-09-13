<?php

namespace App\Presenters\v1;

use App\Presenters\BasePresenter;

class OrderPresenter extends BasePresenter
{
    public function list()
    {
        // countOffers() — это отдельный COUNT, поэтому считаем один раз и
        // переиспользуем и для выдачи, и для новых откликов.
        $countOffers = $this->countOffers();
        $view = $this->seenState();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'count_offers' => $countOffers,
            // Карточка в ленте показывает бюджет — до сих пор цены были
            // только в detail(). Отдаём числом: форматирование разрядов —
            // дело клиента, ему всё равно нужны свои разделители под язык.
            'price_recommended' => is_null($this->price_recommended)
                ? null
                : (float) $this->price_recommended,
            'price_max' => is_null($this->price_max)
                ? null
                : (float) $this->price_max,
            'city' => (new CityPresenter($this->city))->list(),
            'created_at' => date('d.m.Y', strtotime($this->created_at)),
            'status' => $this->getStatusName(),
            'status_code' => $this->status,
            'execution_days' => $this->execution_days,
            'status_changed' => !is_null($view) && (int) $view->seen_status !== (int) $this->status,
            'new_offers_count' => is_null($view)
                ? 0
                : max(0, $countOffers - (int) $view->seen_offers_count),
        ];
    }

    // Отметка «просмотрено» смотрящего, если её подгрузили (OrderRepo::index
    // с viewer_id). null — либо список без бейджей, либо заказ, который
    // пользователь ещё ни разу не видел: в обоих случаях нового нет.
    private function seenState()
    {
        if (!$this->model->relationLoaded('views')) {
            return null;
        }

        return $this->model->views->first();
    }

    public function detail()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->getStatusName(),
            'status_code' => $this->status,
            'count_offers' => $this->countOffers(),
            'category' => $this->category ? [
                'id' => $this->category->id,
                'title' => $this->category->title, 
            ] : null,
            // Столбцы nullable: number_format(null) в PHP 8.1 — deprecation,
            // да и «0.00» вместо пустоты клиенту не нужен.
            'price_max' => is_null($this->price_max) ? null : (float) $this->price_max,
            'price_recommended' => is_null($this->price_recommended)
                ? null
                : (float) $this->price_recommended,
            'execution_days' => $this->execution_days,
            'city' => (new CityPresenter($this->city))->list(),
            'user' => $this->user ? (new UserPresenter($this->user))->short() : null,
            'executor' => $this->executor ? (new ExecutorPresenter($this->executor))->short() : null,
            'files' => $this->media ? $this->presentCollections($this->media, MediaFilePresenter::class, 'list') : [],
            'created_at' => date('d.m.Y', strtotime($this->created_at)),
        ];
    }

    public function short()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'created_at' => date('d.m.Y', strtotime($this->created_at)),
        ];
    }
}