<?php

namespace DTApi\Events;

use DTApi\Models\Job;
use DTApi\Events\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class JobWasCreated extends Event
{
    use SerializesModels;
    var $job;
    var $data;
    var $exclude_user_id;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Job $job, $data = [], $exclude_user_id = '*')
    {
        $this->job = $job;
        $this->data = $data;
        $this->exclude_user_id = $exclude_user_id;
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
