<?php

return [

    /**
     * The class containing the core logic. It must be
     * either the delegate itself, or for custom
     * functionality – a class which extends
     * ROTGP\AuthSodium\AuthSodiumDelegate.
     */
    'delegate' => ROTGP\AuthSodium\AuthSodiumDelegate::class,
    
    /**
     * Options for retrieving the user model, and it's
     * attributes.
     */
    'user' => [
        
        /**
         * The model class to be used for all auth
         * operations. The only requirements are that
         * the model should extend
         * `Illuminate\Database\Eloquent\Model`, and
         * should implement
         * `Illuminate\Contracts\Auth\Authenticatable`.
         * For convenience, the model may simply extend
         * `ROTGP\AuthSodium\Models\AuthSodiumUser`
         * which already meets these requirements. This
         * configuration is REQUIRED, and may NOT be
         * null.
         */
        'model' => null,
        
        /**
         * The name of the model field/column used to
         * uniquely identify the user. This is to be
         * used in conjunction with the `Auth-User`
         * header value that is to be sent with every
         * authenticate request. As the name suggests -
         * the value of this field should be unique to
         * the user. It may be an id (provided that the
         * end user knows their own id), a username, an
         * email address, or anything else.
         */
        'unique_identifier' => 'email',
        
        /**
         * The name of the model field/column used for
         * storing the user's public key. The public key
         * should be encoded as per the `encoding`
         * config option, ie, base64 (by default) or
         * hex.
         */
        'public_key_identifier' => 'public_key',
    ],
    
    /**
     * A string to 'glue' together the pieces of the
     * signature array together. The default is an empty
     * string.
     */
    'glue' => '',
    
    /**
     * The names of the headers which are to be sent
     * with every authenticated request.
     */
    'header_keys' => [
        
        /**
         * The nonce, a random number or counter to be
         * sent with each request,
         */
        'nonce' => 'Auth-Nonce',
        
        /**
         * The timestamp of when the request is being
         * made. This is the number of milliseconds (or
         * seconds if you're using a 32-bit version of
         * PHP), since midnight January 1st 1970 (UTC)
         */
        'timestamp' => 'Auth-Timestamp',
        
        /**
         * The unique identifier for the user making the
         * authenticated request. See config value of
         * `user.unique_identifier`. This should be
         * something known to the user, such as their
         * email address, or their username.
         */
        'user_identifier' => 'Auth-User',
        
        /**
         * The signature of the following array,
         * imploded using the config value of `glue`,
         * and signed by the user's private key: 
         *  - HTTP method: get, put, post, or delete
         *  - full URL (excluding query string)
         *  - json-encoded alphabetically sorted query
         *    data array
         *  - json-encoded post data array
         *  - user identifier
         *  - timestamp
         *  - nonce
         */
        'signature' => 'Auth-Signature'
    ],
    
    /**
     * Options for how the auth sodium middleware should
     * be applied to requests in an automated way.
     */
    'middleware' => [
        
        /**
         * Return a string to add AuthSodium middleware
         * to a middleware group automatically. For
         * example, 'web' or 'api'.
         */
        'group' => null,
        
        /**
         * Return a string to identify the AuthSodium
         * middleware. Return null if you don't wish to
         * define a dedicated middleware (ie, if using
         * guards, or appending the middleware to
         * another group).
         *
         * Assuming a string is returned, (such as
         * 'authsodium'), then the middleware can be
         * applied in several different ways.
         *
         * Per route:
         *
         * `Route::resource('foos',
         * FooController::class)->middleware('authsodium');`
         *
         * Per controller (in the contoller's
         * constructor):
         *
         * public function __construct()
         * {
         *    $this->middleware('authsodium'); //
         *    $this->middleware('authsodium')->only('index');
         *    //
         *    $this->middleware('authsodium')->except('index');
         * }
         *
         * etc
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
        'global' => false,
        
        /**
         * If true, requests will be aborted
         * automatically when the middleware is run and
         * the request is lacking the appropriate auth
         * signature (or associated headers).
         *
         * If false, then requests lacking a valid
         * signature will proceed, but Auth::user() will
         * be null.
         */
        'abort_on_invalid_signature' => true
    ],
    
    /**
     * How the Auth guard and facade are to be accessed.
     * The default guard name is null, which is to say
     * that the Auth facade should be used directly
     * like: `Auth::user()` instead of with an alias
     * like `Auth::guard('customName')->user()`
     * 
     * Return a string to identify the guard name,
     * which is essentially an alias. If this is
     * specified, then the Auth facade itself will
     * be remain untouched, and instead you can use
     * Auth::guard('name'). For example, given the
     * name: 'authsodium', instead of using
     * Auth::user(), you would use
     * `Auth::guard('authsodium')->user()`. See more
     * examples as follows:
     *
     * - Auth::guard('authsodium')->check() // bool
     *
     * - Auth::guard('authsodium')->user() // object|null
     *
     * - Auth::guard('authsodium')->id() // int|string|null
     *
     * - Auth::guard('authsodium')->guest() // bool
     *
     * - Auth::guard('authsodium')->authenticateSignature()
     *   // bool
     *
     * - Auth::guard('authsodium')->invalidate() // bool
     */
    'guard' => null,
    
    /**
    * This should be considered an extra precaution as
    * the auth user should disappear when the app
    * terminates, however, it may prove to be useful in
    * the future (long-running processes, octane, swoole
    * etc). Keep in mind that this package is intended
    * for stateless authentication.
    */
    'invalidate_user' => [
        
        /**
         * If true, and your server supports terminating
         * middleware, then Auth::invalidate will be
         * called explicitly after the response has been
         * sent to the browser. 
         */
        'after_request' => true,

        /**
         * If true, an attempt will be made via the
         * app's `terminating` method, however,
         * for long-running processes this is less
         * useful.
         */
        'on_terminate' => true,
    ],
    
    /**
     * Options regarding nonce pruning.
     */
    'prune' => [
        
        /**
         * Check that the nonce table exists before
         * pruning. It may not exist in some cases (such
         * as on terminating the application and before
         * migrations have been performed). If you're
         * sure the nonces tables exists, then set to
         * false for a slight performance optimization.
         */
        'check_table_exists' => true,
        
        /**
         * Prune nonces on terminating a request (via
         * middleware). As per invalidate_user.on_terminate,
         * this will only apply if using middleware, and
         * the server supports it.
         */
        'after_request' => true,
        
        /**
         * Prune nonces when the application terminates.
         * This also includes when run via the cli.
         */
        'on_terminate' => false,
        
        /**
         * Specify a time (as a string) to schedule
         * nonces to be pruned at a daily time. The
         * format should be in 24-hour time, as hours
         * and minutes. Examples:
         *
         *  - '00.00' // midnight
         *  - '12.00' // midday
         *  - '11.30' // 11:30am
         *  - '23.45' // 11:45pm
         *
         * The laravel scheduler must be set up for this
         * to work, see here for more info
         * https://laravel.com/docs/8.x/scheduling#running-the-scheduler
         *
         * Of course, custom scheduling may be created
         * by providing the `authsodium:prune` command
         * as the argument.
         */
        'daily_at' => null
    ],
    
    /**
     * The encoding used for request signatures, and
     * also for the user's public key.
     */
    'encoding' => 'base64', // or 'hex'
    
    /**
     * The leeway (in milliseconds, unless you're using
     * a 32-bit version of PHP, in which case it is in
     * seconds), on either side of the timestamp, in
     * which to allow valid timestamps. A leeway of
     * 300000 milliseconds (the default) equates to a
     * request timestamp within 5 minutes (before or
     * after) the current system timestamp being
     * accepted. The larger the value, the more
     * forgiving the service, but this will also result
     * in more nonces being stored at any given time.
     * This, however, should not be a concern, as nonce
     * deletion is managed automatically. Please note
     * that this value should not exceed an hour.
     *
     * 300000 milliseconds = 300 seconds = 5 minutes
     */ 
    'leeway' => 300000,
    
    /**
     * Configure how failed authentication attempts are
     * managed.
     */
    'throttle' => [
        
        /**
         * Whether or not throttling is currently
         * enabled.
         */
        'enabled' => true,
        
        /**
         * The invervals (in milliseconds, unless you're
         * using a 32-bit version of PHP, in which case
         * it is in seconds) after which a new
         * authentication attempt can be made, after
         * having made an initial failed one. Zero
         * indicates that an attempt can be made
         * immediately. Intervals are relative to the
         * preceding one, so the default would allow
         * three consecutive immediate attempts, then an
         * attempt in 1 second, then 3 seconds following
         * that, etc. After the last attempt fails, the
         * user is considered to be blocked.
         */
        'decay' => [0, 0, 0, 1000, 3000],
        
        /**
         * Throttling will not be applied at all for
         * these environments.
         */
        'exclude_environments' => ['local'],
        
        /**
         * If true (the default), will only throttle
         * automated middleware authentications, not
         * explicit calls such as
         * `Auth::authenticateSignature()` or
         * `Auth::guard('authsodium')->authenticateSignature()`
         */
        'middleware_only' => true
    ],
    
    /**
     * Options for convenience routes. By default, none
     * are provided.
     */
    'routes' => [

        /**
         * Provide a route name such as 'auth/validate'
         * which will point to the `validate` method of
         * `ROTGP\AuthSodium\Http\Controllers\AuthSodiumController`.
         * The request should be a simple signed GET
         * request to the route name provided, with no
         * query or post data. The user is then
         * authenticated and returned. If the
         * authentication should fail, then the
         * appropriate codes will be returned. 
         */
        'validate' => null
    ],
    
    /**
     * Options for enforcing secure HTTPS/TLS
     * connections.
     */
    'secure' => [
        
        /**
         * The environments in which HTTPS/TLS
         * connections are to be enforced. Requests made
         * with insecure schemes in these environments
         * will fail.
         */
        'environments' => ['production'],
        
        /**
         * The schemes which are acceptable in secure
         * environments. This should only ever really be
         * https, however, other schemes do exist, such
         * as 'wss' (secure web sockets).
         */
        'schemes' => ['https']
    ],

    /**
     * Schema options, which should be configured prior
     * to running migrations.
     */
    'schema' => [
        
        /**
         * The nonce (number used once) which must be
         * provided for each authenticated request.
         */
        'nonce' => [
            
            /**
             * The length of the nonce for the database
             * column. By default it's 44, which is 32
             * base64 encoded bytes. For hex encoding,
             * the length should be 64. Not that this is
             * just a plain string (or int). It is
             * convenient to generate random bytes with
             * a CSPRNG and encode them as hex or
             * base64, but in the end it's just a
             * string.
             */
            'length' => 44,
            
            /**
             * Whether or not the nonce should be unique
             * per user/timestamp.
             *
             * If true, then a unique constraint for
             * user/nonce/timestamp will be created at
             * database level, meaning that a nonce can
             * be reused if it has a different
             * timestamp. A request with a repeating
             * user/nonce/timestamp will still be
             * rejected if the timestamp does not fall
             * within `leeway` of the system
             * time. This allows for more margin or
             * error (random nonces being repeated), as
             * the nonces must only be unique within
             * `leeway` of the system time.
             *
             * If false (the default), then the unique
             * constraint will be for the user/nonce,
             * regardless of the timestamp. So, if
             * user/nonce is repeated (even if days
             * apart), an exception will occur. This
             * means that in order to avoid conflicts,
             * nonces should be cleared regularly (the
             * default).
             *
             * In either case, using 256-bit nonces
             * generated by a CSPRNG should be more than
             * sufficient to ensure no accidental
             * collisions occur.  More discussion here:
             * https://stackoverflow.com/a/6876907/1985175
             * https://crypto.stackexchange.com/a/41173/4557
             */
            'unique_per_timestamp' => false
        ]
    ],
    
    /**
     * The HTTP status codes to be returned according to
     * different outcomes. When to use which code is
     * subjective, so these can be conigured.
     */
    'http_status_codes' => [
        
        /**
         * When all the associated metadata has been
         * provided and validated, but the signature is
         * invalid.
         */
        'unauthorized' => 401,
        
        /**
         * User has been blocked because they have
         * exceeded the allowable amount of failed
         * authentication requests, as defined in
         * `throttle.decay`, or because their account is
         * not currently enabled (as defined by calling
         * the 'enabled' method on the model, and
         * receiving a false result).
         */
        'forbidden' => 403,
        
        /**
         * The user and IP address have attempted too
         * many failed authenticated requests, and a
         * period of time must be observed before
         * attempting again.
         */
        'too_many_requests' => 429,
        
        /**
         * Some metadata related to the authentication
         * was incorrect, invalid, or missing. This is
         * something that can be fixed by the client.
         * Examples:
         * - nonce_not_found
         * - nonce_exceeds_max_length
         * - timestamp_not_found
         * - nonce_already_exists
         * - signature_not_found
         * - signature_invalid_length
         * - invalid_timestamp_format
         * - onValidationError
         * - user_identifier_not_found
         * - user_public_key_identifier_not_found
         * - user_public_key_not_found
         * - onValidationError
         * - unable_to_build_signature_string
         * - invalid_signature_encoding
         * - invalid_public_key_encoding
         */
        'validation_error' => 422, // some people prefer 400
        
        /**
         * https://stackoverflow.com/questions/2554778/what-is-the-proper-http-response-to-send-for-requests-that-require-ssl-tls
         */
        'secure_protocol_required' => 426
    ],

    /**
     * General error codes which may be customized.
     * These are used in conjunction with HTTP status
     * codes to provide further information regarding
     * the error.
     */
    'error_codes' => [
        
        /**
         * Unable to find the specified Auth-User
         */
        'user_not_found' => null,
        
        /**
         * The specified Auth-User was found, but they
         * are not currently enabled
         */
        'user_not_enabled' => null,
        
        /**
         * No identifier for the auth user was found in
         * the headers
         */
        'user_identifier_not_found' => null,
        
        /**
         * No identifier for the auth user's public key
         * was found in the headers
         */
        'user_public_key_identifier_not_found' => null,
        
        /**
         * Unable to locate the public key for the
         * specified Auth-User
         */
        'user_public_key_not_found' => null,
        
        /**
         * All the right information was provided, but
         * the signature was found to be invalid
         */
        'invalid_signature' => null,
        
        /**
         * The signature was not found in the headers of
         * the request
         */
        'signature_not_found' => null,
        
        /**
         * The timestamp was not found in the headers of the
         * request
         */
        'timestamp_not_found' => null,
        
        /**
         * The timestamp format is invalid - most likely
         * not an integer
         */
        'invalid_timestamp_format' => null,
        
        /**
         * The timestamp provided in the headers falls
         * outside of the acceptable range (defined by `leeway`)
         */
        'invalid_timestamp_range' => null,
        
        /**
         * The signature string was unable to be built
         * (for an indeterminate reason)
         */
        'unable_to_build_signature_string' => null,
        
        /**
         * The nonce was not found in the headers of the
         * request
         */
        'nonce_not_found' => null,
        
        /**
         * The nonce is too long
         */
        'nonce_exceeds_max_length' => null,
        
        /**
         * The nonce already exists for this user
         */
        'nonce_already_exists' => null,
        
        /**
         * Too many failed requests have been made for a
         * user/ip-address, and the length of time
         * defined by `try_again` must be observed
         * before trying again
         */
        'too_many_requests_please_wait' => null,
        
        /**
         * Too many failed requests have been made for a
         * user/ip-address, and no further attempts are
         * forbidden.
         */
        'too_many_requests_forbidden' => null,
        
        /**
         * TLS/SSL secure protocol is not being used 
         */
        'secure_protocol_required' => null,
        
        /**
         * The signature encoding is invalid
         */
        'invalid_signature_encoding' => null,
        
        /**
         * The public key encoding is invalid
         */
        'invalid_public_key_encoding' => null,
    ],
];
