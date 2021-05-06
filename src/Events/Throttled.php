<?php

namespace ROTGP\AuthSodium\Events;

use Illuminate\Queue\SerializesModels;

class Throttled
{
    use SerializesModels;

    /**
     * The authentication guard name.
     *
     * @var string
     */
    public $guard;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * The originating ip address.
     *
     * @var \ROTGP\AuthSodium\Models\Throttle
     */
    public $throttle;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function __construct($guard, $user, $throttle)
    {
        $this->user = $user;
        $this->guard = $guard;
        $this->throttle = $throttle;
    }
}