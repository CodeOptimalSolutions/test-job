<?php

namespace DTApi\Events;

use DTApi\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use DTApi\Models\Job;
use Illuminate\Support\Facades\Mail;

class JobWasCanceled extends Event
{
    use SerializesModels;
    var $job;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Job $job)
    {
        $adminemail = config('app.admin_email');
        $adminSenderEmail = config('app.admin_sender_email');
        $this->job = $job;

        $user = $job->user()->get()->first();

        $canclee = $job->status == 'pending' ? 'translator' : 'customer';
        $translator = Job::getJobsAssignedTranslatorDetail($job);
        if( $canclee == 'customer'){
            $name = @$user->name;
        } else {
            $name = @$translator->name;
        }


        Mail::send('emails.job-cancel-admin', ['user' => $user , 'job' => $job, 'canclee' => $canclee , 'name' => $name ], function ($m) use ($user, $job, $adminSenderEmail, $adminemail) {
            $m->from($adminSenderEmail, 'DigitalTolk');
            $m->to($adminemail, 'Admin')->subject('Avbokning av bokningsnr: #' . $job->id . '');
        });

        
        //cancel by customer
        if ($job->status == 'pending') {

            Mail::send('emails.job-cancel-user', ['user' => $user, 'job' => $job], function ($m) use ($user, $job, $adminSenderEmail) {
                $m->from($adminSenderEmail, 'DigitalTolk');
                if( !empty( $job->user_email ) ){
                    $m->to($job->user_email, $user->name)->subject('Avbokning av bokningsnr: #' . $job->id . '');
                } else {
                    $m->to($user->email, $user->name)->subject('Avbokning av bokningsnr: #' . $job->id . '');
                }
            });

        } else {
            $translator = Job::getJobsAssignedTranslatorDetail($job);
            if( $translator ){
                Mail::send('emails.job-cancel-translator', ['user' => $translator, 'job' => $job], function ($m) use ($translator, $job, $adminSenderEmail) {
                    $m->from($adminSenderEmail, 'DigitalTolk');
                    $m->to($translator->email, $translator->name)->subject('Avbokning av bokningsnr: #' . $job->id . '');
                });
            }
        }


    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
