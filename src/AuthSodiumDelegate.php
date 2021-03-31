<?php

namespace ROTGP\AuthSodium;

use Symfony\Component\HttpFoundation\Response;
use Auth;
use Exception;

class AuthSodiumDelegate
{
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
    protected function getSignatureNonce($request)
    {
        // @TODO validate nonce format, existence, etc
        return $request->header(
            config(
                'authsodium.header_keys.nonce',
                'Auth-Nonce'
            )
        );
    }

    /**
     * Return a UTC-timezone unix timestamp of when the
     * request was made.
     *
     * @return int
     */
    protected function getSignatureTimestamp($request)
    {
        // @TODO validate timestamp format, existence, etc
        // and that timestamp falls within acceptable range
        return $request->header(
            config(
                'authsodium.header_keys.timestamp',
                'Auth-Timestamp'
            )
        );
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
        return $request->header(
            config(
                'authsodium.header_keys.user_identifier',
                'Auth-User'
            )
        );
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
        if ($array === null || sizeof($array) === 0) {
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
        return config('authsodium.guard.name', 'authsodium');
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
    protected function userUniqueIdentifier()
    {
        return config(
            'authsodium.user.unique_identifier',
            'email'
        );
    }

    // the field used to uniquely identify the user
    protected function userPublicKey()
    {
        return config(
            'authsodium.user.public_key',
            'public_key'
        );
    }

    protected function retrieveSignature($request)
    {
        $signature = $request->header(
            config(
                'authsodium.header_keys.signature',
                'Auth-Signature'
            )
        );

        // @TODO validate that not empty or null
        if (empty($signature)) {
            $this->errorResponse(null, 400, ['nope' => 'no signature found']);
        }

        return $this->decode($signature);
    }

    protected function retrievePublicKey($user)
    {
        $publicKey = $user[$this->userPublicKey()];
        // @TODO validate public key
        return $this->decode($publicKey);
    }

    protected function retrieveUser($request)
    {
        $model = $this->authUserModel();
        
        // @TODO validate that $uniqueIdentifier is not null or empty
        $uniqueIdentifier = $this->getUserIdentifier($request);
        
        // https://stackoverflow.com/questions/29407818/is-it-possible-to-temporarily-disable-event-in-laravel/51301753
        $dispatcher = $model::getEventDispatcher();

        $model::unsetEventDispatcher();

        $user = $model::firstWhere($this->userUniqueIdentifier(), $uniqueIdentifier);

        $model::setEventDispatcher($dispatcher);

        // @TODO validate that user is not null
        if ($user === null) {
            $this->errorResponse(null, 400, ['nope' => 'no user found']);
        }

        return $user;
    }

    public function buildSignatureString($request)
    {        
        $toSign = [];
        $toSign['method'] = $this->getSignatureMethod($request);
        $toSign['url'] = $this->getSignatureUrl($request);
        $toSign['query_data'] = $this->getSignatureQuery($request);
        $toSign['post_data'] = $this->getSignaturePostdata($request, $toSign['method']);
        $toSign['user_identifier'] = $this->getUserIdentifier($request);
        $toSign['nonce'] = $this->getSignatureNonce($request);
        $toSign['timestamp'] = $this->getSignatureTimestamp($request);
        
        // dd(implode($this->glue(),
        // array_values($toSign)));
        
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
        // dd($message);
        $signatureIsValid = sodium_crypto_sign_verify_detached(
            $signatureToVerify,
            $message,
            $publicKey
        );

        if ($signatureIsValid !== true) {
            $this->errorResponse(null, 400, ['nope' => 'invalid signature']);
        }
        
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
        $modelNS = config('authsodium.user.model');
        
        if (empty($modelNS)) {
            throw new Exception('Auth sodium model not defined');
        }
        
        if (!class_exists($modelNS)) {
            throw new Exception('Auth sodium model class: "' . $modelNS . '" not found');
        }
        
        return new $modelNS;
    }

    protected function errorResponse($errorCode = null, int $httpStatusCode = 401, $extras = []) : void
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

        // @TODO implement error codes
        // if ($errorCode !== null)
        //     $responseData['error_code'] = $errorCode;
        // $errorKey = $this->findErrorKey($errorCode);
        // if ($errorKey !== null)
        //     $responseData['error_message'] = $this->translateErrorMessage(strtolower($errorKey));
        if (sizeof($extras) > 0) {
            $responseData = array_merge($responseData, $extras);
        }
        abort(response()->json($responseData, $httpStatusCode));
    }
}
