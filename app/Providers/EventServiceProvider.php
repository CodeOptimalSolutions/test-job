<?php

namespace DTApi\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'DTApi\Events\SomeEvent' => [
            'DTApi\Listeners\EventListener',
        ],
        'DTApi\Events\JobWasCreated' => [
            'DTApi\Listeners\SendNotification',
            'DTApi\Listeners\SendEmailNotifcation',
            'DTApi\Listeners\SendNotificationTranslator',
        ],
        'DTApi\Events\JobWasCanceled' => [
            'DTApi\Listeners\NotifyTranslator',
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        //
    }
}
