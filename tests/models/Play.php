<?php

namespace ROTGP\AuthSodium\Test\Models;

use Illuminate\Database\Eloquent\Model;

class Play extends Model
{
    protected $fillable = [
        'song_id',
        'user_id',
        'streaming_service_id',
        'listen_time'
    ];

    public function song()
    {
        return $this->belongsTo(Song::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function streamingService()
    {
        return $this->belongsTo(StreamingService::class);
    }
}
