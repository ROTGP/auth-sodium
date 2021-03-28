<?php

namespace ROTGP\AuthSodium;

use Symfony\Component\HttpFoundation\Response;
use Auth;
use Exception;

class AuthSodiumDelegate {

    /**
     * Return the method of the 
     * incoming request. Ie, get,
     * put, post or delete. We
     * use lowercase as standard.
     *
     * @return string
     */
    protected function getSignatureMethod($request)
    {
        return strtolower($request->method());
    }

    /**
     * Return the url of the incoming
     * request, WITHOUT the query
     * string. Valid examples: 
     * 
     * https://foo.com
     * http://foo.com/bar
     * https://www.foo.com/bar/2
     * 
     * Invalid example:
     * 
     * https://foo.com/bar/2?foo=bar
     *
     * @return string
     */
    protected function getSignatureUrl($request)
    {
        return strtolower($request->url());
    }

    /**
     * Return a nonce for the request.
     * It can be anything, (string, 
     * int), a counter, whatever, as
     * long as the user making the 
     * request hasn't used it within 
     * the current timestamp window. 
     * Given that it must only be 
     * unique to the user, and that 
     * nonces older than the current
     * timestamp window are deleted, 
     * a 32 byte CSPRNG-generated
     * base64-encoded string would 
     * work well.
     * 
     *
     * @return string|int
     */
    protected function getSignatureNonce($request)
    {
        // @TODO validate nonce format, existence, etc
        return $request->header('Nonce');
    }

    /**
     * Return a UTC-timezone unix 
     * timestamp of when the request
     * was made.
     *
     * @return int
     */
    protected function getSignatureTimestamp($request)
    {
        // @TODO validate timestamp format, existence, etc
        // and that timestamp falls within acceptable range 
        return $request->header('Timestamp');
    }

    /**
     * Return a unique identifier for the 
     * user being authorized. It may be 
     * an id, a name, an email, or anything
     * really. However, the user/client
     * should know this prior to sending
     * the request, so an email address
     * or username works well. 
     *
     * @return string|int
     */
    protected function getSignatureUser($request)
    {
        // @TODO validate timestamp format, existence, etc
        // and that timestamp falls within acceptable range 
        return $request->header('User');
    }

    /**
     * Return a the query data as a string.
     * Special care needs to be taken as
     * different servers/browsers may treat
     * query data differently. For example,
     * sending '?b=banana&a=apple' may
     * arrive to our application sorted, ie: 
     * ?a=apple&b=banana. Other examples 
     * includes empty values, utf encoding, 
     * etc. So we attempt to standardize the
     * query data as much as possible.
     *
     * @return string
     */
    protected function getSignatureQuery($request)
    {
        // get query string params, this will always be an associative array
        $query = $request->query();

        if (sizeof($query) === 0) return '';

        // make array keys lowercase as uppercase affects sorting
        $query = array_change_key_case($query, CASE_LOWER);
        
        // sort by array key
        ksort($query);

        // replace null values with empty strings
        $query = array_map(function($value) {
            return $value === null ? '' : $value;
         }, $query);

        return $this->jsonEncode($query);
    }

    /**
     * Return a the put/post data as a string. 
     *
     * @return string
     */
    protected function getSignaturePostdata($request)
    {
        // get query string params, this will always be an associative array
        $postData = $request->post();

        if (sizeof($postData) === 0) return '';

        $postData = array_map(function($value) {
            return $value === null ? '' : $value;
         }, $postData);

        return $this->jsonEncode($postData);
    }

    /**
     * Return a string to 'glue' together
     * the pieces of the signature array. 
     *
     * @return string
     */
    protected function glue()
    {
        return '';
    }


    /**
     * Return a string to identify the
     * AuthSodium guard name. 
     *
     * @return string
     */
    public function guardName()
    {
        return 'authsodium';
    }

    /**
     * Return a string to identify the
     * AuthSodium middleware. Return 
     * null if you don't wish to define
     * a dedicated middleware (ie, if 
     * using guards, or appending the
     * middleware to another group).
     * 
     * Assuming we return a string, (
     * such as 'authsodium'), then we
     * can apply the middleware in
     * several different ways.
     * 
     * Per route:
     * 
     *  Route::resource('foos', FooController::class); //->middleware('authsodium');
     * 
     * Per controller (in the 
     * contoller's constructor): 
     * 
     *  public function __construct()
     *  {
     *     $this->middleware('authsodium');
     *     // $this->middleware('authsodium')->only('index');
     *     // $this->middleware('authsodium')->except('index');
     * }
     * 
     * See more here: https://laravel.com/docs/8.x/middleware
     *
     * @return string
     */
    public function middlewareName()
    {
        return 'authsodium';
    }

    /**
     * Return a string to add AuthSodium 
     * middleware to a middleware
     * group. For example, 'web' or 'api'.
     *
     * @return string
     */
    public function middlewareGroup()
    {
        return null;
    }

    /**
     * Return true to run AuthSodium 
     * middleware implicitly on all
     * requests. False by default as
     * it's not very flexible.
     *
     * @return bool
     */
    public function useGlobalMiddleware()
    {
        return false;
    }

    protected function encode($value)
    {
        return base64_encode($value);
    }

    protected function decode($value)
    {
        return base64_decode($value);
    }

    protected function jsonEncode($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // the field used to uniquely identify the user
    protected function userIdentifier()
    {
        return 'email';
    }

    protected function retrieveSignature($request)
    {
        $signature = $request->header('Signature');

        // @TODO validate that not empty or null
        if (empty($signature)) 
            $this->errorResponse(null, 400, ['nope' => 'no signature found']);

        return $this->decode($signature);
    }

    protected function retrievePublicKey($user)
    {
        return $this->decode($user->public_key);
    }

    protected function retrieveUser($request)
    {
        $model = $this->authUserModel();
        
        // @TODO validate that $uniqueIdentifier is not null or empty
        $uniqueIdentifier = $request->header('User');

        // https://stackoverflow.com/questions/29407818/is-it-possible-to-temporarily-disable-event-in-laravel/51301753
        $dispatcher = $model::getEventDispatcher();

        $model::unsetEventDispatcher();

        $user = $model::firstWhere($this->userIdentifier(), $uniqueIdentifier);

        $model::setEventDispatcher($dispatcher);

        // @TODO validate that user is not null
        if ($user === null) 
            $this->errorResponse(null, 400, ['nope' => 'no user found']);

        return $user;
    }

    public function buildSignatureString($request)
    {
        $toSign = [];
        $toSign['method'] = $this->getSignatureMethod($request);
        $toSign['url'] = $this->getSignatureUrl($request);
        $toSign['nonce'] = $this->getSignatureNonce($request);
        $toSign['timestamp'] = $this->getSignatureTimestamp($request);
        $toSign['query_data'] = $this->getSignatureQuery($request);
        $toSign['post_data'] = $this->getSignaturePostdata($request);
        $toSign['user_identifier'] = $this->getSignatureUser($request);

        return implode($this->glue(), array_values($toSign));
    }

    public function validateRequest($request)
    {
        // dd('here we are', $request->route()->getName(), $request->route()->uri(), $request->route());
        // return null;
        $user = $this->retrieveUser($request);
        $publicKey = $this->retrievePublicKey($user);
        $signatureToVerify = $this->retrieveSignature($request);
        $message = $this->buildSignatureString($request);
        $signatureIsValid = sodium_crypto_sign_verify_detached(
            $signatureToVerify,
            $message,
            $publicKey
        );

        if ($signatureIsValid !== true)
            $this->errorResponse(null, 400, ['nope' => 'invalid signature']);
        
        return $user;
    }

    // protected function authorizeUser($user)
    // {
    //     Auth::login($user);
    //     Auth::guard($this->middlewareName())->login($user);
    // }

    /**
     * Return an instance of the user-defined User model.
     *
     * @return ROTGP\AuthSodium\Models\AuthSodiumUser
     */
    public function authUserModel()
    {
        $modelNS = config('authsodium.model');
        
        if (!$modelNS)
            throw new Exception('Auth sodium model not defined');
        
        if (!class_exists($modelNS))
            throw new Exception('Auth sodium model class: "' . $modelNS . '" not found');
        
        return new $modelNS;
    }

    protected function errorResponse($errorCode = null, int $httpStatusCode = 500, $extras = []) : void
    {        
        if (!is_int($httpStatusCode))
            throw new Exception('HTTP status code is required');

        if (!array_key_exists($httpStatusCode, Response::$statusTexts))
            throw new Exception('HTTP status code not found');

        $responseData = [
            'http_status_code' => $httpStatusCode,
            'http_status_message' => Response::$statusTexts[$httpStatusCode]
        ];

        // @TODO implement error codes
        // if ($errorCode !== null) 
        //     $responseData['error_code'] = $errorCode;
        // $errorKey = $this->findErrorKey($errorCode);
        // if ($errorKey !== null)
        //     $responseData['error_message'] = $this->translateErrorMessage(strtolower($errorKey));
        if (sizeof($extras) > 0)
            $responseData = array_merge($responseData, $extras);
        abort(response()->json($responseData, $httpStatusCode));
    }
}