<?php

return [

    /**
     * The class containing core the logic. Must point
     * directly to either the delegate, or if custom
     * functionality is desired, a class which extends
     * ROTGP\AuthSodium\AuthSodiumDelegate.
     */
    'delegate' => ROTGP\AuthSodium\AuthSodiumDelegate::class,
    

    'user' => [
        /**
         * The model class to be used for all auth
         * operations. The only requirements are that the
         * model should extend
         * Illuminate\Database\Eloquent\Model, and should
         * implement
         * Illuminate\Contracts\Auth\Authenticatable. For
         * convenience's sake, the model may extend
         * ROTGP\AuthSodium\Models\AuthSodiumUser.
         */
        'model' => null,

        /**
         * The name of the model field/column used to
         * uniquely identify the user.
         */
        'unique_identifier' => 'email',

        /**
         * The name of the model field/column used for
         * storing the public key
         */
        'public_key' => 'public_key',
    ],

    /**
     * Return a string to 'glue' together the pieces of
     * the signature array together.
     */
    'glue' => '',
    
    'header_keys' => [
        'nonce' => 'Auth-Nonce',
        'timestamp' => 'Auth-Timestamp',
        'user_identifier' => 'Auth-User',
        'signature' => 'Auth-Signature'
    ],

    'guard' => [

        /**
         * Return a string to identify the AuthSodium
         * guard name.
         */
        'name' => 'authsodium',
    ],

    'middleware' => [

        /**
         * Return a string to add AuthSodium middleware
         * to a middleware group. For example, 'web' or
         * 'api'.
         */
        'group' => null,

        /**
         * Return a string to identify the AuthSodium
         * middleware. Return null if you don't wish to
         * define a dedicated middleware (ie, if using
         * guards, or appending the middleware to
         * another group).
         *
         * Assuming we return a string, (such as
         * 'authsodium'), then we can apply the
         * middleware in several different ways.
         *
         * Per route:
         *
         *  Route::resource('foos',
         *  FooController::class);
         *  //->middleware('authsodium');
         *
         * Per controller (in the contoller's
         * constructor):
         *
         *  public function __construct()
         *  {
         *     $this->middleware('authsodium');
         *     // $this->middleware('authsodium')->only('index');
         *     // $this->middleware('authsodium')->except('index');
         * }
         *
         * See more here:
         * https://laravel.com/docs/8.x/middleware
         */
        'name' => 'authsodium',

        /**
         * Return true to run AuthSodium middleware
         * implicitly on all requests. False by default
         * as it's not very flexible.
         */
        'use_global' => false,

        /**
         * @TODO
         * If true, requests will be aborted when the
         * middleware is run and the request is lacking
         * the appropriate auth signature (or associated
         * headers).
         *
         * If false, then requests lacking a signature
         * will proceed, but Auth::user() will be null.
         */
        'abort_with_invalid_signatures' => true,
    ]


    /**
     * @TODO implement customizable error codes
     *
     * ie,
     * 'missing_signature' => 1,
     * 'user_not_found' => 2,
     * 'missing_nonce' => 3,
     * 'nonce_reused' => 4,
     */
];
