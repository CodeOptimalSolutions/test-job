<?php

namespace DTApi\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \DTApi\Console\Commands\CronJobs::class,
        \DTApi\Console\Commands\Emergency::class,
        \DTApi\Console\Commands\SetCustomerId::class,
        \DTApi\Console\Commands\MobileNumbers::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('digitaltolk:cron')->everyMinute()->when(function () {
            return true;
        })->sendOutputTo( storage_path().'/logs/cron.log' );
    }
}
