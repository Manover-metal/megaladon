<?php

use App\Http\Controllers\Api\v1\{
    AdvertController,
    ExecutorController,
    OrderOfferController,
    UserController, 
    AuthController,
    CatalogController,
    ChatController,
    CityController,
    InvoiceController,
    OpenStreetMapController,
    OrderController,
    ProductCategoryController,
    StoreController,
    SubscriptionController,
    ServiceTypeController,
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['guard' => 'api'], function () {
    Route::get('/cities', [CatalogController::class, 'cities']);
    Route::get('/order-categories', [CatalogController::class, 'orderCategories']);
    Route::get('/advert-categories', [CatalogController::class, 'advertCategories']);
    Route::get('/address', [OpenStreetMapController::class, 'getAddress']);
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::get('/service-types', [ServiceTypeController::class, 'index']);
    Route::get('/company-types', [StoreController::class, 'types']);
    
    Route::group(['prefix' => '/auth'], function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/confirm-code', [AuthController::class, 'confirmCode']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/pusher-login', [AuthController::class, 'pusherLogin'])->middleware('api');
        
        Route::group(['middleware' => 'api'], function () {
            // Route::post('/send-code', [AuthController::class, 'sendCode']);
            Route::post('/register-executor', [AuthController::class, 'registerExecutor']);
            Route::post('/register-store', [AuthController::class, 'registerStore']);
            Route::delete('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::group(['prefix' => 'user', 'middleware' => 'api'], function () {
        Route::post('/change-password', [UserController::class,'changePassword']);
        Route::put('/store', [StoreController::class, 'updateProfile']);
        Route::put('/executor', [ExecutorController::class, 'update']);
        Route::post('/update-photo', [UserController::class, 'updatePhoto']);
        Route::post('/change-phone/start', [UserController::class, 'startChangePhone']);
        Route::post('/change-phone/end', [UserController::class, 'endChangePhone']);
        Route::post('/change-token', [UserController::class, 'updateToken']);
        Route::delete('/disable-notifications', [UserController::class, 'disableNotifications']);
        Route::get('/push-status', [UserController::class, 'pushStatus']);
        Route::post('/change-push-status', [UserController::class, 'changePushStatus']);
        Route::get('/profile', [UserController::class, 'currentProfile']);
        Route::get('/{id}', [UserController::class, 'profile']);
    });

    Route::group(['prefix' => 'invoice', 'middleware' => 'api'], function () {
        Route::post('/executor/create', [InvoiceController::class, 'executorCreate']);
        Route::post('/store/create', [InvoiceController::class, 'storeCreate']);
        Route::post('/{id}/paid', [InvoiceController::class, 'paid']);
    });

    Route::group(['prefix' => 'order'], function () {
        Route::get('/', [OrderController::class, 'index']);

        Route::group(['middleware' => 'api'], function () {
            Route::get('/my', [OrderController::class, 'indexMy']);
            Route::get('/my-responded', [OrderController::class, 'indexMyResponded']);
            Route::post('/create', [OrderController::class, 'store']);
            Route::post('/{id}/update', [OrderController::class, 'update']);
            Route::delete('/{id}/delete', [OrderController::class, 'delete']);
            Route::post('/{id}/complete', [OrderController::class, 'complete']);
            Route::post('/{id}/rate', [OrderController::class, 'rate']);
            Route::post('/{id}/chat/create', [OrderController::class, 'createChat']);
            Route::post('/{id}/offer', [OrderOfferController::class, 'create']);
            Route::get('/{id}/offer', [OrderOfferController::class, 'orderOffers']);
            Route::get('/{id}/offer/{offerId}', [OrderOfferController::class, 'info']);
            Route::post('/{id}/offer/{offerId}', [OrderOfferController::class, 'accept']);
        });
        Route::get('/{id}', [OrderController::class, 'info']);
    });

    Route::group(['prefix' => 'adverts'], function() {
        Route::get('/', [AdvertController::class, 'index']);
        Route::get('/my', [AdvertController::class, 'indexMy']);
        Route::get('/{id}', [AdvertController::class, 'info']);

        Route::group(['middleware' => 'api'], function () {
            Route::post('/', [AdvertController::class, 'create']);
            Route::post('/{id}/update', [AdvertController::class, 'update']);
            Route::delete('/{id}/delete', [AdvertController::class, 'delete']);
            Route::post('/{id}/chat/create', [AdvertController::class, 'createChat']);
        });
    });

    Route::group(['prefix' => 'store', 'middleware' => 'api'], function() {
        Route::get('/', [StoreController::class, 'index']);
        Route::get('/{id}', [StoreController::class, 'info']);
        Route::post('/{id}/rate', [StoreController::class, 'rate']);
        Route::put('/update', [StoreController::class, 'updateProfile']);
        Route::post('/price', [StoreController::class, 'uploadPrice']);
        Route::post('/price/{id}/activate', [StoreController::class, 'activatePrice']);
        Route::post('/price/{id}/deactivate', [StoreController::class, 'deactivatePrice']);
        Route::delete('/price/{id}/delete', [StoreController::class, 'deletePrice']);
    });

    Route::group(['prefix' => 'executor', 'middleware' => 'api'], function () {
        Route::get('/favorite', [ExecutorController::class, 'indexMy']);
        Route::post('/favorite', [ExecutorController::class, 'addToFavorites']);
    });

    Route::group(['prefix' => 'chat', 'middleware' => 'api'], function () {
        Route::get('/', [ChatController::class, 'getChats']);
        Route::post('/send-message', [ChatController::class, 'sendMessage']);
        Route::get('/{id}', [ChatController::class, 'getMessages']);
        Route::put('/edit-message/{id}', [ChatController::class, 'editMessage']);
        Route::delete('/delete-message/{id}', [ChatController::class, 'deleteMessage']);
    });
});