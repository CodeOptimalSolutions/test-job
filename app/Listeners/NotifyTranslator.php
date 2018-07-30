<?php

namespace DTApi\Listeners;

use DTApi\Events\JobWasCanceled;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyTranslator
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  JobWasCanceled  $event
     * @return void
     */
    public function handle(JobWasCanceled $event)
    {
        //

        $job = $event->job;


    }
}
