<?php

namespace DTApi\Events;

use DTApi\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class JobTest extends Event implements ShouldBroadcast
{
    use SerializesModels;

    public $name;
    public $content;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->name = 3;
        $this->content = [ 'text' => 'Job accepted'];
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
