<?php

namespace DTApi\Events;

use DTApi\Models\Job;
use DTApi\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SessionStarted extends Event implements ShouldBroadcast
{
    use SerializesModels;

    /**
     * @var mixed
     */
    public $name;
    public $content;

    /**
     * Create a new event instance.
     *
     * @param Job $job
     */
    public function __construct(Job $job)
    {
        $this->name = $job->user_id;
        $this->content = [ 'text' => 'Booking started'];
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return ['notify-channel'];
    }
}
