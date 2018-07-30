<?php

namespace DTApi\Listeners;

use DTApi\Events\JobWasCreated;
use DTApi\Repository\BookingRepository;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNotificationTranslator
{

    protected $bookingRepository;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->bookingRepository = $bookingRepository;
    }

    /**
     * Handle the event.
     *
     * @param  JobWasCreated  $event
     * @return void
     */
    public function handle(JobWasCreated $event)
    {

        $data = $event->data;
        $job = $event->job;
        $exclude_user_id = $event->exclude_user_id;
        $this->bookingRepository->sendNotificationTranslator($job, $data, $exclude_user_id);

    }

}
