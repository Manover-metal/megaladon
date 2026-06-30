<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::get('charts', 'ChartController@index');
    Route::crud('user', 'UserCrudController');
    Route::crud('ad-category', 'AdCategoryCrudController');
    Route::crud('city', 'CityCrudController');
    Route::crud('service-type', 'ServiceTypeCrudController');
    Route::crud('order-category', 'OrderCategoryCrudController');
    Route::crud('subscription', 'SubscriptionCrudController');
    Route::crud('invoice', 'InvoiceCrudController');
    Route::crud('executor', 'ExecutorCrudController');
    Route::crud('store', 'StoreCrudController');
    Route::crud('advert', 'AdvertCrudController');
    Route::crud('order', 'OrderCrudController');
    Route::crud('order-offer', 'OrderOfferCrudController');
}); // this should be the absolute last line of this file