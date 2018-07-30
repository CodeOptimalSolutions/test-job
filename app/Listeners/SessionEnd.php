<?php

namespace DTApi\Listeners;

use DTApi\Events\SessionEnded;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SessionEnd
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
     * @param  SessionEnded  $event
     * @return void
     */
    public function handle(SessionEnded $event)
    {
        //
    }
}
