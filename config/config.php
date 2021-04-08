<?php

return [

    /**
     * The class containing core the logic. Must point
     * directly to either the delegate, or if custom
     * functionality is desired, a class which extends
     * ROTGP\AuthSodium\AuthSodiumDelegate.
     */
    'delegate' => ROTGP\AuthSodium\AuthSodiumDelegate::class,
    
    'nonce' => [

        /**
         * The length of the nonce for the database
         * column. By default it's 44, which is 32
         * base64 encoded bytes.
         */
        'length' => 44,
    ],
    

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
        'public_key_identifier' => 'public_key',
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
         * Return a string to identify the guard name,
         * which is essentially an alias. If this is
         * specified, then the Auth facade itself will
         * be remain untouched, and instead you can use
         * Auth::guard('name'). For example, given the
         * name: 'authsodium'...
         *
         * - Auth::guard('authsodium')->check() // bool
         * 
         * - Auth::guard('authsodium')->user() //
         *   object|null
         * 
         * - Auth::guard('authsodium')->id() //
         *   int|string|null
         * 
         * - Auth::guard('authsodium')->guest() // bool
         * 
         * - Auth::guard('authsodium')->authenticateSignature()
         *   // bool
         * 
         * - Auth::guard('authsodium')->invalidateUser()
         *   // bool
         *
         */
        'name' => null
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
         * If true, requests will be aborted when the
         * middleware is run and the request is lacking
         * the appropriate auth signature (or associated
         * headers).
         *
         * If false, then requests lacking a signature
         * will proceed, but Auth::user() will be null.
         */
        'abort_on_invalid_signature' => true,

        /**
         * This shouldn't be necessary, but it can be
         * taken as an extra precaution.
         *
         * https://laravel.com/docs/8.x/middleware#terminable-middleware
         *
         * If true, and your server supports terminating
         * middleware, then Auth::invalidateUser will explicitly
         * be called after the response has been sent to
         * the browser.
         */
        'log_out_after_request' => true,
    
    ],

    'encoding' => 'base64', // or 'hex'

    'validation_http_error_code' => 422, // some people prefer 400
    'authorization_failed_http_code' => 401,

    'error_codes' => [
        'user_not_found' => 0,
        'user_identifier_not_found' => 0,
        'user_public_key_identifier_not_found' => 0,
        'user_public_key_not_found' => 0,
        'invalid_signature' => 0,
        'signature_not_found' => 0,
        'timestamp_not_found' => 0,
        'invalid_timestamp_format' => 0,
        'invalid_timestamp_range' => 0,
        'unable_to_build_signature_string' => 0,
        'nonce_not_found' => 0,
        'nonce_already_exists' => 0,
    ],

    'timestamp' => [
        /**
         * the leeway (in seconds), on either side of
         * the timestamp, in which to allow valid
         * timestamps. A leeway of 60 equates to a
         * request timestamp within one hour (before or
         * after) of the current system timestamp being
         * accepted. A value of 2 will result in
         * timestamps only 2 minutes either side of the
         * current system time being accepted. The
         * larger the value, the more forgiving the
         * service, but this will also result in more
         * nonces being stored at any given time. This,
         * however, should not be a concern, as nonce
         * deletion is managed automatically.
         */ 
        'leeway' => 300,
    ]
];
