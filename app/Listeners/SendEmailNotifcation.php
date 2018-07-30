<?php

namespace DTApi\Listeners;

use DTApi\Events\JobWasCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmailNotifcation
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
     * @param  JobWasCreated  $event
     * @return void
     */
    public function handle(JobWasCreated $event)
    {
        $job=$event->job;
//        if($job->job_type=='paid')
//        {
//            /**/
//
//            $users = User::getAllUserByRoleId(Constants::$translator_role_id);  /* get all translators */
//            foreach($users as $user)
//            {
//                $meta = $user->userMeta()->get()->all();  /* vol or professional */
//            }
//        }
//        else
//        {
//            $users = User::getAllUserByRoleId(Constants::$translator_role_id);  /* get all translators */
//            foreach($users as $user)
//            {
//
//            }
//
//        }
    }
}
