<?php

namespace App\Services\v1;

use App\Models\AdCategory;
use App\Models\OrderCategory;
use App\Models\ProductCategory;
use App\Presenters\v1\AdvertPresenter;
use App\Presenters\v1\OrderCategoryPresenter;
use App\Services\BaseService;

class CatalogService extends BaseService
{
    public function orderCategories()
    {
        // Корневые категории помечены parent_id = 0 (так пишет сидер), а не NULL.
        // Поддерживаем оба варианта, чтобы запрос не зависел от способа заливки.
        $categories = OrderCategory::with('child')
            ->where(function ($query) {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->get();
        return $this->resultCollections($categories, OrderCategoryPresenter::class, 'list');
    }

    public function productCategories()
    {
        $productCategories = ProductCategory::with('child')->where('parent_id', 0)->get();
        return $this->resultCollections($productCategories, OrderCategoryPresenter::class, 'list');
    }

    public function advertCategories()
    {
        // Корневые помечены parent_id = 0 (сидер), а не NULL — матчим оба варианта.
        $advertCategories = AdCategory::with('child')
            ->where(function ($query) {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->get();
        return $this->resultCollections($advertCategories, AdvertPresenter::class, 'categories');
    }
}