<?php

namespace App\Providers;

use App\Events\ExecutorRatedEvent;
use App\Events\NewMessageEvent;
use App\Events\OfferAcceptedEvent;
use App\Events\OfferCreatedEvent;
use App\Events\StoreRatedEvent;
use App\Listeners\ExecutorRatedListener;
use App\Listeners\NewMessageListener;
use App\Listeners\OfferAcceptedListener;
use App\Listeners\OfferCreatedListener;
use App\Listeners\StoreRatedListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        ExecutorRatedEvent::class => [
            ExecutorRatedListener::class,
        ],

        StoreRatedEvent::class => [
            StoreRatedListener::class,
        ],

        OfferCreatedEvent::class => [
            OfferCreatedListener::class,
        ],
        
        OfferAcceptedEvent::class => [
            OfferAcceptedListener::class,
        ],

        NewMessageEvent::class => [
            NewMessageListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
