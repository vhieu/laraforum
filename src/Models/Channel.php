<?php

namespace Exp\Laraforum\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    /**
     * @return threads created with this channel
     */
    public function threads()
    {
        return $this->hasMany(Thread::class);
    }
    public function getNameAttribute($value)
    {
        return strtolower($value);
    }
}
