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
        $categories = OrderCategory::with('child')->whereNull('parent_id')->get();
        return $this->resultCollections($categories, OrderCategoryPresenter::class, 'list');
    }

    public function productCategories()
    {
        $productCategories = ProductCategory::with('child')->where('parent_id', 0)->get();
        return $this->resultCollections($productCategories, OrderCategoryPresenter::class, 'list');
    }

    public function advertCategories()
    {
        $advertCategories = AdCategory::with('child')->whereNull('parent_id')->get();
        return $this->resultCollections($advertCategories, AdvertPresenter::class, 'categories');
    }
}