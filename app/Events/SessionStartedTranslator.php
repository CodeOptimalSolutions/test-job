<?php

namespace DTApi\Events;

use DTApi\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SessionStartedTranslator extends Event implements ShouldBroadcast
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
     * @return void
     */
    public function __construct($user_id)
    {
        $this->name = $user_id;
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
