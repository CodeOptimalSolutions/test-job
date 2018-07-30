<?php

namespace DTApi\Events;

use DTApi\Events\Event;
use DTApi\Models\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SessionEnded extends Event implements ShouldBroadcast
{
    use SerializesModels;

    public $name;
    public $content;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Job $job, $user_id)
    {
        $this->name = $user_id;
        $this->content = [ 'text' => 'Tack! Vi har nu registrerat tolktiden', 'action' => 'booking_ended', 'job_id' => $job->id];
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
