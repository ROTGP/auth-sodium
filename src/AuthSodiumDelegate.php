<?php

namespace ROTGP\AuthSodium;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Schema;

use Symfony\Component\HttpFoundation\Response;

use ROTGP\AuthSodium\Models\Nonce;

use Auth;
use Closure;
use Exception;
use Carbon\Carbon;

class AuthSodiumDelegate implements Guard
{
    protected $user;
    protected $isMiddleware;

    public function foo($value)
    {
        return ('original: ' . $value);
    }

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
     * Get the currently authenticated user.
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
     * Get the ID for the currently authenticated user.
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
    public function invalidateUser()
    {
        if ($this->isGuard()) {
            $this->user = null;
        } else {
            Auth::invalidateUser();
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
     * If there is no user currently authenticated, then
     * try to authenticate one, based on the current
     * request. Return whether or not we were able to
     * authenticate a user.
     *
     * @return bool
     */
    public function authenticateSignature()
    {
        if ($this->getUser()) {
            return true;
        }
        return $this->validateRequest(request(), false);
    }

    public function handle($request, Closure $next)
    {
        $this->validateRequest($request, true);
        
        return $next($request);
    }

    /**
     * https://laravel.com/docs/8.x/middleware#terminable-middleware
     *
     * If you define a terminate method on your middleware, it will automatically be
     * called after the response is sent to the browser.
     */
    public function terminate($request, $response)
    {
        if (config('authsodium.log_out_after_request', true)) {
            $this->invalidateUser();
        }

        if (config('authsodium.prune_nonces_after_request', true)) {
            $this->pruneNonces();
        }
    }

    public function pruneNonces()
    {
        if (config('authsodium.check_nonces_table_before_pruning', true) && 
            !Schema::hasTable('nonces')) {
            return;
        }
        $leeway = $this->getTimestampLeeway();
        // $cutoff = $this->getSystemTime()->subtract($leeway, 'seconds')->timestamp;
        
        $cutoff = $this->getSystemTime() - $leeway;
        Nonce::where('timestamp', '<', $cutoff)->delete();
    }

    /**
     * Return the method of the incoming request. Ie,
     * get, put, post or delete. We use lowercase as
     * standard.
     *
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
     * @return string
     */
    protected function getSignatureUrl($request)
    {
        return strtolower($request->url());
    }

    /**
     * Return a nonce for the request. It can be
     * anything, (string, int), a counter, whatever, as
     * long as the user making the request hasn't used
     * it within the current timestamp window. Given
     * that it must only be unique to the user, and that
     * nonces older than the current timestamp window
     * are deleted, a 32 byte CSPRNG-generated
     * base64-encoded string would work well.
     *
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

    protected function getTimestampLeeway()
    {
        return config('authsodium.timestamp.leeway', 300000);
    }

    protected function getAppTimezone()
    {
        return 'UTC';
    }

    protected function getUniquePerTimestamp()
    {
        return config('authsodium.schema.nonce_unique_per_timestamp', false);
    }

    /**
     * Returns milliseconds since midnight January 1st
     * 1970 (UTC)
     */
    public function getSystemTime()
    {
        dd('WTFFFFF???');
        return config('authsodium.timestamp.use_milliseconds', true) ?
            intval(microtime(true) * 1000) : time();
    }

   /**
     * Validate the existence, length etc of signature,
     * not whether it's value is valid;
     */
    protected function validateNonce($value, $user, $timestamp)
    {
        if (!$value) {
            $this->onValidationError('nonce_not_found');
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

        // dd($query->toSql(), $query->getBindings());
        
        $found = $query->first();
        
        if ($found) {
            $this->onValidationError('nonce_already_exists');
            return null;
        }
        
        return $value;
    }

    /**
     * Validate the existence, length etc of signature,
     * not whether it's value is valid;
     */
    protected function validateSignature($value)
    {
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

    protected function validateTimestamp($value)
    {
        if (empty($value)) {
            $this->onValidationError('timestamp_not_found');
            return null;
        }
        
        if (!ctype_digit($value) || $value > PHP_INT_MAX) {
            $this->onValidationError('invalid_timestamp_format');
            return null;
        }

        $value = intval($value);

        $leeway = $this->getTimestampLeeway();
        $now = $this->getSystemTime();
        // $requestTimestamp = Carbon::createFromTimestamp($value);
        $difference = abs($now - $value); // abs($now->diffInSeconds($requestTimestamp));

        // // dd($fooVal);
        // if ($difference > $leeway) {
        //     dd([
        //         'now        ' => $now,
        //         'incoming_ts' => $value,
        //         'diff       ' => $difference,
        //         'leeway     ' => $leeway,
        //         'too much   ' => ($difference > $leeway),
        //     ]);
        // }
        
        if ($difference > $leeway) {
            $this->onValidationError('invalid_timestamp_range');
            return null;
        }

        return $value;
    }

    /**
     * Return a unix timestamp of when the request was
     * made.
     *
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
     * @return void
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
     * etc. So we attempt to standardize the query data
     * as much as possible.
     *
     * @return string
     */
    protected function getSignatureQuery($request)
    {
        return $this->stringify($request->query(), true);
    }

    /**
     * Return a the put/post data as a string.
     *
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
     * @return string
     */
    public function guardName()
    {
        return config('authsodium.guard.name', null);
    }

    /**
     * Return whether or not we're using a guard.
     *
     * @return bool
     */
    public function isGuard()
    {
        return !empty($this->guardName());
    }

    /**
     * Return whether or not we're using a guard.
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
     * Assuming we return a string, (such as
     * 'authsodium'), then we can apply the middleware
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
        return config('authsodium.middleware.use_global', false);
    }

    protected function encode($value)
    {
        return $this->useBase64() ? base64_encode($value) : bin2hex($value);
    }

    protected function decode($value)
    {
        if (empty($value)) {
            return $value;
        }
        return $this->useBase64() ? base64_decode($value) : hex2bin($value);
    }

    protected function useBase64()
    {
        return config('authsodium.encoding', 'base64');
    }

    protected function jsonEncode($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // the field used to uniquely identify the user
    protected function userUniqueIdentifier()
    {
        return config(
            'authsodium.user.unique_identifier',
            'email'
        );
    }

    // the field used to uniquely identify the user
    protected function userPublicKeyIdentifier()
    {
        return config(
            'authsodium.user.public_key_identifier',
            'public_key'
        );
    }

    protected function validationErrorCode()
    {
        return config(
            'authsodium.validation_error_code',
            422
        );
    }

    /**
     * All the required information was provided, but
     * the signature verification failed.
     */
    protected function authorizationFailedCode()
    {
        return config(
            'authsodium.authorization_failed_http_code',
            401
        );
    }

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
        
        return $this->decode($publicKey);
    }

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

        if ($user === null || !$user) {
            $this->onValidationError('user_not_found');
            return null;
        }
        
        return $user;
    }

    public function buildSignatureString($request, $user)
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

    protected function abortOnInvalidSignature()
    {
        return $this->isMiddleware && config(
            'authsodium.middleware.abort_on_invalid_signature',
            true
        );
    }

    public function validateRequest($request, $isMiddleware)
    {
        // $x = config('authsodium.yum', 'oooo');
        // dd($x, 'xxxzax', \App::environment(), \App::environment(), \App::environment(), \App::environment(), $this->abortOnInvalidSignature());

        \DB::enableQueryLog();
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
        
        $signatureIsValid = sodium_crypto_sign_verify_detached(
            $signatureToVerify,
            $message,
            $publicKey
        );

        if (!$signatureIsValid) {
            $this->invalidateUser();
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
            'user_id' => $user->getAuthIdentifier(),
            'value' => $nonce,
            'timestamp' => $timestamp
        ]);

        $this->setUser($user);
        
        return true;
    }

    /**
     * Return an instance of the user-defined User model.
     *
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

    protected function translateErrorMessage($value)
    {
        return ucfirst(strtolower(str_replace('_', ' ', $value)));
    }

    protected function appendErrorData(&$payload, $error)
    {
        $payload['error_key'] = $error;
        $payload['error_code'] = config('authsodium.error_codes.' . $error, null);
        $payload['error_message'] = $this->translateErrorMessage($error);
    }

    protected function onValidationError($errorKey)
    {
        if ($this->abortOnInvalidSignature()) {
            $httpStatusCode = config('authsodium.validation_http_error_code', 422);
            $this->errorResponse($errorKey, $httpStatusCode);
        }
    }

    protected function errorResponse($errorKey, $httpStatusCode = 401, $extras = []) : void
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
