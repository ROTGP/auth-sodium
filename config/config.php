<?php

return [

    /**
     * The class containing core the logic. Must point
     * directly to either the delegate it, or if custom
     * functionality is desired, a class which extends 
     * ROTGP\AuthSodium\AuthSodiumDelegate.
     */
    'delegate' => ROTGP\AuthSodium\AuthSodiumDelegate::class,

    /**
     * The model to be used for all auth operations. 
     * The model must extend ROTGP\AuthSodium\Models\User.
     */
    'model' => ROTGP\AuthSodium\Models\AuthSodiumUser::class,

    /**
     * The route prefix for API requests such as 
     * email verification, password resets, etc. Use
     * null for no prefix.
     */
    'prefix' => 'auth',

    'routes' => [

        // The route for verifying email addresses.
        'email_verification' => 'verify',

        'foo' => 'bar',
    ],
];