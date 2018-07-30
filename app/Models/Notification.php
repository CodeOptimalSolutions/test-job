<?php

namespace DTApi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'type', 'subject', 'body', 'object_id', 'object_type', 'sent_at'];

    public function getDates()
    {
        return ['created_at', 'updated_at', 'sent_at'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //-- Notification--
    public function withSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    public function withBody($body)
    {
        $this->body = $body;

        return $this;
    }

    public function withType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function regarding($object)
    {
        if (is_object($object)) {
            $this->object_id = $object->id;
            $this->object_type = get_class($object);
        }

        return $this;
    }

    // --End notification --
    public function deliver()
    {
        $this->sent_at = new Carbon();
        $this->save();

        return $this;
    }
}
