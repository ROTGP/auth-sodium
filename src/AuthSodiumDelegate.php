<?php

namespace ROTGP\AuthSodium;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Schema;

use Symfony\Component\HttpFoundation\Response;

use ROTGP\AuthSodium\Models\Nonce;
use ROTGP\AuthSodium\Models\Throttle;

use ROTGP\AuthSodium\Events\Invalidated;
use ROTGP\AuthSodium\Events\Throttled;
use ROTGP\AuthSodium\Events\Blocked;

use Auth;
use Closure;
use Request;
use Exception;

use DateTime;
use DateTimeZone;

class AuthSodiumDelegate implements Guard
{
    /**
     * The authenticated user (in the case of using a
     * custom guard).
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $user;
    
    /**
     * Whether or not middleware is being used.
     *
     * @var bool
     */
    protected $isMiddleware;

    
    /**
     * Whether or not the request should be aborted when
     * a signature is found to be invalid.
     *
     * @var bool
     */
    protected $abortOnInvalidSignature;
    

    /**
     * Whether or not throttling should be performed.
     *
     * @var bool
     */
    protected $shouldThrottle;

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        $this->authenticateSignature();
        return ! is_null($this->getUser());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        $this->authenticateSignature();
        return ! $this->check();
    }

    /**
     * Attempt to authenticate and then get the
     * currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        $this->authenticateSignature();
        return $this->getUser();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function getUser()
    {
        return $this->isGuard() ? $this->user : Auth::user();
    }

    /**
     * Get the id for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        $this->authenticateSignature();
        if ($this->getUser()) {
            return $this->getUser()->getAuthIdentifier();
        }
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        throw new Exception("Method 'validate' not supported");
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        if ($this->isGuard()) {
            $this->user = $user;
            $this->fireAuthenticatedEvent($user);
        } else {
            Auth::setUser($user);
        }
    }
    
    /**
     * Invalidate currently authenticated user.
     *
     * @return void
     */
    public function invalidate()
    {
        $user = $this->getUser();
        
        if ($this->isGuard()) {
            $this->user = null;
        } else {
            Auth::invalidate();
        }
        
        if ($user) {
            $this->fireInvalidatedEvent($user);
        }
    }

    /**
     * Fire the authenticated event for the guard/user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function fireAuthenticatedEvent($user)
    {
        if ($this->guardName()) {
            // https://laravel.com/docs/8.x/authentication#events
            event(new Authenticated(
                $this->guardName(), $user
            ));
        }
    }

    /**
     * Fire the Invalidated event for the guard/user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function fireInvalidatedEvent($user)
    {
        event(new Invalidated(
            $this->guardName() ?? Auth::getDefaultDriver(), $user
        ));
    }

    /**
     * Fire the Attempting event for the guard/user.
     *
     * @param  array  $credentials
     * @return void
     */
    protected function fireAttemptingEvent($credentials)
    {
        event(new Attempting(
            $this->guardName() ?? Auth::getDefaultDriver(), $credentials, false
        ));
    }
    
    /**
     * Fire the Invalidated event for the guard/user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return void
     */
    protected function fireFailedEvent($user, $credentials)
    {
        event(new Failed(
            $this->guardName() ?? Auth::getDefaultDriver(), $user, $credentials
        ));
    }

    /**
     * Fire the Throttled event for the guard/user/throttle.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \ROTGP\AuthSodium\Models\Throttle  $throttle
     * @return void
     */
    protected function fireThrottledEvent($user, $throttle)
    {
        event(new Throttled(
            $this->guardName() ?? Auth::getDefaultDriver(), $user, $throttle
        ));
    }

    /**
     * Fire the Blocked event for the guard/user/throttle.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \ROTGP\AuthSodium\Models\Throttle  $throttle
     * @return void
     */
    protected function fireBlockedEvent($user, $throttle)
    {
        event(new Blocked(
            $this->guardName() ?? Auth::getDefaultDriver(), $user, $throttle
        ));
    }
    
    /**
     * If there is no user currently authenticated, then
     * try to authenticate one, based on the current
     * request. Return whether or not it was possible to
     * authenticate the user.
     *
     * @return bool
     */
    public function authenticateSignature()
    {
        if ($this->getUser()) {
            return true;
        }
        return $this->validateRequest(false);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->validateRequest(true);
        
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
        if (config('authsodium.invalidate_user.after_request', true)) {
            $this->invalidate();
        }

        if (config('authsodium.prune.after_request', true)) {
            $this->pruneNonces();
        }
    }

    /**
     * Delete nonces that are older than the leeway
     * period, and return the number deleted.
     * @return int
     */
    public function pruneNonces()
    {
        if (config('authsodium.prune.check_table_exists', true) && 
            !Schema::hasTable('authsodium_nonces')) {
            return;
        }
        $leeway = $this->getTimestampLeeway();
        $cutoff = $this->getSystemTime() - $leeway;
        return Nonce::where('timestamp', '<', $cutoff)->delete();
    }

    /**
     * Delete a user's throttle.
     * 
     * @param  mixed  $authUserIdentifier
     * @param  string  $ipAddress
     * @return bool
     */
    public function deleteThrottle($authUserIdentifier, $ipAddress)
    {
        if (!Schema::hasTable('authsodium_throttles') || 
            ($authUserIdentifier === null && $ipAddress === null)
            ) {
            return false;
        }

        $query = Throttle::query();

        if ($authUserIdentifier) {
            $query->forUserIdentifier($authUserIdentifier);
        }

        if ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        }

        return $query->delete();
    }
    
    /**
     * Return the method of the incoming request. Ie,
     * get, put, post or delete. Lowercase is used as
     * standard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getSignatureMethod($request)
    {
        return strtolower($request->method());
    }

    /**
     * Return the full url of the incoming request,
     * including the http protocol, but WITHOUT the
     * query string.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getSignatureUrl($request)
    {
        return strtolower($request->url());
    }

    /**
     * Return a nonce for the request.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $validate
     * @param  int  $timestamp
     * @return string|int
     */
    protected function getSignatureNonce($request, $user, $validate = true, $timestamp = null)
    {
        $nonce = $request->header(
            config(
                'authsodium.header_keys.nonce',
                'Auth-Nonce'
            )
        );

        if ($validate) {
            $nonce = $this->validateNonce($nonce, $user, $timestamp);
        }
        
        return $nonce;
    }

    /**
     * Get the leeway for the timestamp.
     * 
     * @return int
     */
    protected function getTimestampLeeway()
    {
        return config('authsodium.leeway', 300000);
    }

    /**
     * Get whether nonces are unique per timestamp.
     * 
     * @return bool
     */
    protected function getUniquePerTimestamp()
    {
        return config('authsodium.schema.nonce.unique_per_timestamp', false);
    }

    /**
     * Get whether your PHP version is 64 bit (false
     * means 32 bit).
     * 
     * @return bool
     */
    protected function is64Bit()
    {
        return PHP_INT_SIZE === 8;
    }

    /**
     * Get the max length for a nonce.
     *
     * @return int
     */
    public function getNonceMaxLength()
    {
        return config('authsodium.schema.nonce.length', 44);
    }

    
    /**
     * Get throttle decay values.
     *
     * @return array
     */
    protected function getThrottleDecay()
    {
        return config('authsodium.throttle.decay', [0, 0, 0, 1000, 3000]);
    }

        
    /**
     * Get secure TLS environments.
     *
     * @return array
     */
    protected function getSecureEnvironments()
    {
        return config('authsodium.secure.environments', ['production']);
    }

    /**
     * Get milliseconds (or seconds, depending on
     * config) since midnight January 1st 1970 (UTC).
     * 
     * @param  bool  $useMilliseconds
     * @return int
     */
    protected function getSystemTime()
    {
        return $this->is64Bit() ? intval(microtime(true) * 1000) : time();
    }
    
    /**
     * Validate the nonce and return it.
     *
     * @param  string $value
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  int  $timestamp
     * @return string|null
     */
    protected function validateNonce($value, $user, $timestamp)
    {
        if (!$value) {
            $this->onValidationError('nonce_not_found');
            return null;
        }

        if (strlen($value) > $this->getNonceMaxLength()) {
            $this->onValidationError('nonce_exceeds_max_length');
            return null;
        }

        if (!$timestamp) {
            $this->onValidationError('timestamp_not_found');
            return null;
        }

        $authIdentifier = $user->getAuthIdentifier();

        $query = Nonce::forUserIdentifier($authIdentifier)
            ->where('value', $value);

        if ($this->getUniquePerTimestamp()) {
            $query->where('timestamp', $timestamp);
        }
        
        $found = $query->first();
        
        if ($found) {
            $this->onValidationError('nonce_already_exists');
            return null;
        }
        
        return $value;
    }

    /**
     * Validate the existence, length etc of signature,
     * not whether it's value is valid, and then return
     * it. 
     *
     * @param  string $value
     * @return string|null
     */
    protected function validateSignature($value)
    {
        if ($value === false) {
            $this->onValidationError('invalid_signature_encoding');
            return null;
        }

        if (empty($value)) {
            $this->onValidationError('signature_not_found');
            return null;
        }

        if (strlen($value) !== 64) {
            $this->onValidationError('signature_invalid_length');
            return null;
        }

        return $value;
    }

        
    /**
     * Validate that a value is a valid int, whether it
     * be an int, a float, a string (empty or
     * otherwise), or null.
     *
     * @param  mixed $value
     * @return bool
     */
    protected function isValidInt($value)
    {
        $value = strval($value);
        return ctype_digit($value) &&
            $value >= 0 &&
            $value <= PHP_INT_MAX;
    }
    
    /**
     * Validate and return the request timestamp.
     *
     * @param  mixed $value
     * @return int|null
     */
    protected function validateTimestamp($value)
    {
        if (empty($value)) {
            $this->onValidationError('timestamp_not_found');
            return null;
        }
        
        if (!$this->isValidInt($value)) {
            $this->onValidationError('invalid_timestamp_format');
            return null;
        }

        $value = intval($value);
        $leeway = $this->getTimestampLeeway();
        $now = $this->getSystemTime();
        $difference = abs($now - $value);
        
        if ($difference > $leeway) {
            $this->onValidationError('invalid_timestamp_range');
            return null;
        }
        
        return $value;
    }

    /**
     * Return a timestamp of when the request was made
     * (according to the client).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool $validate
     * @return int
     */
    protected function getSignatureTimestamp($request, $validate = true)
    {
        $value = $request->header(
            config(
                'authsodium.header_keys.timestamp',
                'Auth-Timestamp'
            )
        );
        
        if ($validate) {
            $value = $this->validateTimestamp($value);
        }
        
        return $value;
    }

    /**
     * Return a unique identifier for the user being
     * authorized. It may be an id, a name, an email, or
     * anything really. However, the user/client should
     * know this prior to sending the request, so an
     * email address or username works well.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return string|int
     */
    protected function getUserIdentifier($request)
    {
        $uniqueIdentifier = $request->header(
            config(
                'authsodium.header_keys.user_identifier',
                'Auth-User'
            )
        );

        if (empty($uniqueIdentifier)) {
            $this->onValidationError('user_identifier_not_found');
            return null;
        }

        return $uniqueIdentifier;
    }

    /**
     * Turn an array into a string, optionally sorting
     * it alphabetically.
     *
     * @param array $array
     * @param boolean $sort
     * @return string
     */
    protected function stringify($array, $sort = true)
    {
        if (empty($array)) {
            return '';
        }

        if ($sort) {
            $array = array_change_key_case($array, CASE_LOWER);
            ksort($array);
        }
        
        $array = array_map(function ($value) {
            return $value === null ? '' : $value;
        }, $array);

        return $this->jsonEncode($array);
    }

    /**
     * Return a the query data as a string. Special care
     * needs to be taken as different servers/browsers
     * may treat query data differently. For example,
     * sending '?b=banana&a=apple' may arrive to our
     * application sorted, ie:?a=apple&b=banana. Other
     * examples includes empty values, utf encoding,
     * etc. An attempt is made to standardize the query
     * data as much as possible.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getSignatureQuery($request)
    {
        return $this->stringify($request->query(), true);
    }

    /**
     * Return the put/post data as a string.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $method
     * @return string
     */
    protected function getSignaturePostdata($request, $method)
    {
        if (!in_array($method, ['put', 'post']))
            return '';

        return $this->stringify($request->post(), false);
    }

    /**
     * Return a string to 'glue' together the pieces of
     * the signature array.
     *
     * @return string
     */
    protected function glue()
    {
        return config('authsodium.glue', '');
    }


    /**
     * Return a string to identify the AuthSodium guard
     * name.
     *
     * @return string|null
     */
    public function guardName()
    {
        return config('authsodium.guard', null);
    }

    /**
     * Return whether or not a guard is being used.
     *
     * @return bool
     */
    public function isGuard()
    {
        return !empty($this->guardName());
    }

    /**
     * Return whether or not middleware is being used.
     *
     * @return bool
     */
    public function isMiddleware()
    {
        return !$this->isGuard();
    }

    /**
     * Return a string to identify the AuthSodium
     * middleware. Return null if you don't wish to
     * define a dedicated middleware (ie, if using
     * guards, or appending the middleware to another
     * group).
     *
     * Assuming a string is returned, (such as
     * 'authsodium'), then the middleware can be applied
     * in several different ways.
     *
     * @return string
     */
    public function middlewareName()
    {
        return config('authsodium.middleware.name', 'authsodium');
    }

    /**
     * Return a string to add AuthSodium middleware to a
     * middleware group. For example, 'web' or 'api'.
     *
     * @return string
     */
    public function middlewareGroup()
    {
        return config('authsodium.middleware.group', null);
    }

    /**
     * Return true to run AuthSodium middleware
     * implicitly on all requests. False by default as
     * it's not very flexible.
     *
     * @return bool
     */
    public function useGlobalMiddleware()
    {
        return config('authsodium.middleware.global', false);
    }
    
    /**
     * Encode a string to base64 or hex.
     *
     * @param  string $value
     * @return string
     */
    protected function encode($value)
    {
        return $this->useBase64() ? base64_encode($value) : bin2hex($value);
    }
    
    /**
     * Decode a base64 or hex-encoded string.
     *
     * @param  mixed $value
     * @return string|bool
     */
    protected function decode($value)
    {
        if (empty($value)) {
            return $value;
        }
        return $this->useBase64() ? @base64_decode($value, true) : @hex2bin($value);
    }   
    
    /**
     * Return whether to use base64 encoding.
     *
     * @return bool
     */
    protected function useBase64()
    {
        return strtolower(config('authsodium.encoding', 'base64')) === 'base64';
    }
    
    /**
     * JSON-encode an array.
     *
     * @param  array $value
     * @return string
     */
    protected function jsonEncode($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the user's unique identifier (name). 
     *
     * @return string
     */
    protected function userUniqueIdentifier()
    {
        return config(
            'authsodium.user.unique_identifier',
            'email'
        );
    }
    
    /**
     * Get the user's public key identifier (name). 
     *
     * @return string
     */
    protected function userPublicKeyIdentifier()
    {
        return config(
            'authsodium.user.public_key_identifier',
            'public_key'
        );
    }

    /**
     * Return the validation error http code.
     *
     * @return int
     */
    protected function validationErrorCode()
    {
        return config(
            'authsodium.http_status_codes.validation_error',
            422
        );
    }

    /**
     * Return the secure-protocol-required http code.
     *
     * @return int
     */
    protected function secureProtocolRequiredCode()
    {
        return config(
            'authsodium.http_status_codes.secure_protocol_required',
            426
        );
    }
        
    /**
     * Return the unauthorized http code.
     *
     * @return int
     */
    protected function authorizationFailedCode()
    {
        return config(
            'authsodium.http_status_codes.unauthorized',
            401
        );
    }

    
    /**
     * Return the too-many-requests http code.
     *
     * @return int
     */
    protected function tooManyRequestsCode()
    {
        return config(
            'authsodium.http_status_codes.too_many_requests',
            429
        );
    }
    
    /**
     * Return the forbidden http code.
     *
     * @return int
     */
    protected function forbiddenCode()
    {
        return config(
            'authsodium.http_status_codes.forbidden',
            403
        );
    }
    
    /**
     * Retrieve the signature that was sent with the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function retrieveSignature($request)
    {
        $signature = $this->decode(
            $request->header(
                config(
                    'authsodium.header_keys.signature',
                    'Auth-Signature'
                )
            )
        );

        $signature = $this->validateSignature($signature);
        if (!$signature) {
            return null;
        }

        return $signature;
    }
    
    /**
     * Retrieve the user's public key.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return string
     */
    protected function retrievePublicKey($user)
    {
        $userPublicKeyIdentifier = $this->userPublicKeyIdentifier();
        if (empty($userPublicKeyIdentifier)) {
            $this->onValidationError('user_public_key_identifier_not_found');
            return null;
        }

        $publicKey = $user[$userPublicKeyIdentifier];

        if (empty($publicKey)) {
            $this->onValidationError('user_public_key_not_found');
            return null;
        }

        $publicKey = $this->decode($publicKey);

        if ($publicKey === false) {
            /**
             * This is not a validation error because
             * it's nothing that the client can rectify,
             * so return a general error response.
             */
            $this->errorResponse(
                'invalid_public_key_encoding',
                $this->authorizationFailedCode(),
            );
            return null;
        }
        
        return $publicKey;
    }
    
    /**
     * Retrieve the user for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return  \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function retrieveUser($request)
    {
        $model = $this->authUserModel();
        
        $uniqueIdentifier = $this->getUserIdentifier($request);
        if (empty($uniqueIdentifier)) {
            return null;
        }

        $user = $model::withoutEvents(function () use ($model, $uniqueIdentifier) {
            return $model::firstWhere($this->userUniqueIdentifier(), $uniqueIdentifier);
        });

        if (!$user) {
            $this->onValidationError('user_not_found');
            return null;
        }

        if (method_exists($user, 'enabled') && $user->enabled() !== true) {
            $this->errorResponse(
                'user_not_enabled',
                $this->authorizationFailedCode(),
            );
        }
        
        return $user;
    }

    /**
     * Build the signature string to be verified with
     * the user's signature and public key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return string
     */
    protected function buildSignatureString($request, $user)
    {
        $toSign = [];
        $toSign['method'] = $this->getSignatureMethod($request);
        $toSign['url'] = $this->getSignatureUrl($request);
        $toSign['query_data'] = $this->getSignatureQuery($request);
        $toSign['post_data'] = $this->getSignaturePostdata($request, $toSign['method']);
        $toSign['user_identifier'] = $this->getUserIdentifier($request);
        $toSign['timestamp'] = $this->getSignatureTimestamp($request);
        $toSign['nonce'] = $this->getSignatureNonce($request, $user, true, $toSign['timestamp']);
        
        if (in_array(null, array_values($toSign), true)) {
            $this->onValidationError('unable_to_build_signature_string');
            return null;
        }
        return implode($this->glue(), array_values($toSign));
    }
    
    /**
     * Return whether or not the abort the request if
     * the signature is found to be invalid.
     *
     * @return bool
     */
    protected function abortOnInvalidSignature()
    {
        if ($this->abortOnInvalidSignature) {
            return true;
        }
        return $this->isMiddleware && config(
            'authsodium.middleware.abort_on_invalid_signature',
            true
        );
    }

    /**
     * Get the real ip address of the client, despite
     * load balancers.
     * https://stackoverflow.com/a/41769505/1985175
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function getIpAddress($request){
        foreach ([
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
            ] as $key){
            if (array_key_exists($key, $_SERVER) === true){
                foreach (explode(',', $_SERVER[$key]) as $ip){
                    $ip = trim($ip);
                    if (filter_var(
                        $ip,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                        ) !== false){
                        return $ip;
                    }
                }
            }
        }
         // return server ip when no client ip found
        return $request->ip();
    }

    /**
     * Checks if a throttle exists for the user and ip
     * address pertaining to the request. 
     *
     * If none exists then returns immediately.
     *
     * If one exists, then check that the current time
     * is less than or equal to it's try_again value.
     *  - If it's not, then simply abort with a message
     *  - If it is, then return.
     *
     * @param
     * \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \ROTGP\AuthSodium\Models\Throttle
     * $throttle
     * @param  int $now
     * @param  array $decayValues
     * @return void
     */
    protected function preThrottle($user, $throttle, $now, $decayValues)
    {
        /**
         * If no throttle exist then there's no
         * need to continue.
         */
        if (!$throttle) {
            return;
        }
        
        // check if blocked entirely
        if ($throttle->blocked) {
            $this->throttleExhausted();
        }

        if ($now >= $throttle->try_again) {
            return;
        }

        $this->fireThrottledEvent($user, $throttle);
        
        $this->errorResponse(
            'too_many_requests_please_wait',
            $this->tooManyRequestsCode(),
            ['try_again' => $throttle->try_again]
        );
    }
    
    /**
     * Return whether or not to perform throttling.
     *
     * @return bool
     */
    protected function shouldThrottle()
    {
        $enabled = config('authsodium.throttle.enabled', true);
        
        if (!$enabled) {
            return false;
        }

        if ($this->shouldThrottle) {
            return true;
        }

        /**
         * If not middleware and the throttle is middleware-only
         * then return false.
         */
        if (!$this->isMiddleware && config('authsodium.throttle.middleware_only', true)) {
            return false;
        }
        
        $excluded = in_array(
            app()->environment(), 
            config('authsodium.throttle.exclude_environments', ['local'])
        );

        return !$excluded;
    }
    
    /**
     * Return an error response that the user's throttle
     * has been exhausted.
     *
     * @return void
     */
    protected function throttleExhausted()
    {
        $this->errorResponse(
            'too_many_requests_forbidden',
            $this->forbiddenCode(),
        );
    }

        
    /**
     * Update throttle and potentially block user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \ROTGP\AuthSodium\Models\Throttle $throttle
     * @param  array $decayValues
     * @return void
     */
    protected function postThrottle($user, $throttle, $decayValues)
    {
        if ($throttle->id) {
            $throttle->attempts++;
        }

        $decay = $decayValues[$throttle->attempts] ?? 0;
        $throttle->try_again += $decay;
        if ($throttle->attempts >= count($decayValues)) {
            $throttle->blocked = true;
        }
        
        $throttle->save();
        
        if ($throttle->blocked) {
            $this->fireBlockedEvent($user, $throttle);
            $this->throttleExhausted();
        }
    }
    
    /**
     * Get the the http scheme ('http' or 'https').
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getScheme($request)
    {
        return $request->getScheme();
    }
    
    /**
     * Check that a request uses a secure protocol.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function checkSecure($request)
    {
        if (!in_array(
            strtolower(app()->environment()),
            array_map('strtolower', $this->getSecureEnvironments()))
            ) {
            return;
        }
        
        $acceptableSchemes = config('authsodium.secure.schemes', ['https']);
        if (in_array(
            $this->getScheme($request),
            array_map('strtolower', $acceptableSchemes))
            ) {
            return;
        }
        
        $this->errorResponse(
            'secure_protocol_required',
            $this->secureProtocolRequiredCode()
        );
    }
    
    /**
     * Validate that a request has a valid signature.
     *
     * @param  bool $isMiddleware
     * @param  bool $abortOnInvalidSignature
     * @param  bool $shouldThrottle
     * @return void
     */
    public function validateRequest($isMiddleware, $abortOnInvalidSignature = null, $shouldThrottle = null)
    {
        $request = request();
        $this->abortOnInvalidSignature = $abortOnInvalidSignature;
        $this->shouldThrottle = $shouldThrottle;
        $this->checkSecure($request);
        $this->isMiddleware = $isMiddleware;
        
        if ($this->getUser()) {
            return true;
        }

        $signatureToVerify = $this->retrieveSignature($request);
        if (!$signatureToVerify) {
            return false;
        }

        $user = $this->retrieveUser($request);
        if (!$user) {
            return false;
        }

        $publicKey = $this->retrievePublicKey($user);
        if (!$publicKey) {
            return false;
        }
        
        $message = $this->buildSignatureString($request, $user);
        if (!$message) {
            return false;
        }
        
        $authUserIdentifier = $user->getAuthIdentifier();
        $shouldThrottle = $this->shouldThrottle();
        
        if ($shouldThrottle) {
            $ipAddress = $this->getIpAddress($request);
            $now = $this->getSystemTime();
            $decayValues = $this->getThrottleDecay();
            $throttle = Throttle::forUserIdentifier($authUserIdentifier)
                ->where('ip_address', $ipAddress)
                ->first();
            $this->preThrottle($user, $throttle, $now, $decayValues);
        }
        
        
        $credentials = [
            'public_key' => $this->encode($publicKey),
            'message' => $message,
            'signature' => $this->encode($signatureToVerify)
        ];

        $this->fireAttemptingEvent($credentials);
        
        $signatureIsValid = sodium_crypto_sign_verify_detached(
            $signatureToVerify,
            $message,
            $publicKey
        );

        if (!$signatureIsValid) {

            if ($shouldThrottle) {
                $this->postThrottle($user, $throttle ?? new Throttle([
                    'user_id' => $authUserIdentifier,
                    'ip_address' => $ipAddress,
                    'attempts' => 0,
                    'try_again' => $this->getSystemTime(),
                    'blocked' => false
                ]), $decayValues);
            }
            
            $this->fireFailedEvent($user, $credentials);

            $this->invalidate();
            if ($this->abortOnInvalidSignature()) {
                $this->errorResponse(
                    'invalid_signature',
                    $this->authorizationFailedCode()
                );
            }
            return false;
        }

        // save nonce
        $nonce = $this->getSignatureNonce($request, $user, false, null);
        $timestamp = (int) $this->getSignatureTimestamp($request, false);
        Nonce::create([
            'user_id' => $authUserIdentifier,
            'value' => $nonce,
            'timestamp' => $timestamp
        ]);

        /**
         * Authentication was successful, so delete all
         * failed attempts for this user and address
         */ 
        if ($shouldThrottle && $throttle) {
            Throttle::forUserIdentifier($authUserIdentifier)
                ->where('ip_address', $this->getIpAddress($request))->delete();
        }

        $this->setUser($user);
        
        return true;
    }

    /**
     * Return an instance of the user-defined User model.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     */
    public function authUserModel()
    {
        $modelNS = config('authsodium.user.model');
        
        if (empty($modelNS)) {
            throw new Exception('Auth sodium model not defined');
        }
        
        if (!class_exists($modelNS)) {
            throw new Exception('Auth sodium model class: "' . $modelNS . '" not found');
        }

        $model = new $modelNS;

        if (!is_a($model, Model::class)) {
            throw new Exception('Auth sodium model class: "' . $modelNS . '" must extend ' . Model::class);
        }

        if (!is_a($model, Authenticatable::class)) {
            throw new Exception('Auth sodium model class: "' . $modelNS . '" must implement ' . Authenticatable::class);
        }
        
        return $model;
    }
    
    /**
     * Translate the error message.
     *
     * @param  string $value
     * @return string
     */
    protected function translateErrorMessage($value)
    {
        return ucfirst(strtolower(str_replace('_', ' ', $value)));
    }
    
    /**
     * Append error data to the error payload.
     *
     * @param  array $payload
     * @param  string $error
     * @return void
     */
    protected function appendErrorData(&$payload, $error)
    {
        $payload['error_key'] = $error;
        $payload['error_code'] = config('authsodium.error_codes.' . $error, null);
        $payload['error_message'] = $this->translateErrorMessage($error);
    }
    
    /**
     * Abort the request with a validation error.
     *
     * @param  string $errorKey
     * @return void
     */
    protected function onValidationError($errorKey)
    {
        if ($this->abortOnInvalidSignature()) {
            $httpStatusCode = $this->validationErrorCode();
            $this->errorResponse($errorKey, $httpStatusCode);
        }
    }
    
    /**
     * Abort the request with an error.
     *
     * @param  string $errorKey
     * @param  int $httpStatusCode
     * @param  array $extras
     * @return void
     */
    protected function errorResponse($errorKey, $httpStatusCode, $extras = [])
    {
        if (!is_int($httpStatusCode)) {
            throw new Exception('HTTP status code is required');
        }

        if (!array_key_exists($httpStatusCode, Response::$statusTexts)) {
            throw new Exception('HTTP status code not found');
        }

        $responseData = [
            'http_status_code' => $httpStatusCode,
            'http_status_message' => Response::$statusTexts[$httpStatusCode]
        ];
        $this->appendErrorData($responseData, $errorKey);

        if (!empty($extras)) {
            $responseData = array_merge($responseData, $extras);
        }
        abort(response()->json($responseData, $httpStatusCode));
    }
}
