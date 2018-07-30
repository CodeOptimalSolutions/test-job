<?php

namespace DTApi\Providers;

use DTApi\Notifications\INotifications;
use Illuminate\Support\ServiceProvider;
use DTApi\Repository\NotificationsRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('DTApi\Mailers\MailerInterface', 'DTApi\Mailers\AppMailer');
        $this->app->bind('DTApi\Exports\ExportInterface', 'DTApi\Exports\TxtExport');
        $this->app->bind(INotifications::class, NotificationsRepository::class);
    }
}
