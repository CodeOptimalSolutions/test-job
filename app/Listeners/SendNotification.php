<?php

namespace DTApi\Listeners;

use DTApi\Models\Constants;
use DTApi\Models\User;
use DTApi\Events\JobWasCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNotification
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


    public function handle(JobWasCreated $event)
    {
        $job   = $event->job;
        $users = User::getAllUserByRoleId(env('TRANSLATOR_ROLE_ID'));  /* get all translators */
        foreach ($users as $user) {
            $user->newNotification()
                ->withType('new_job_notification')
                ->withSubject('New Job')
                ->withBody('A new job has posted.')
                ->regarding($job)
                ->deliver();
        }
    }
}
