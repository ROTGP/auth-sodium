<?php

namespace ROTGP\AuthSodium;

use ROTGP\AuthSodium\Models\EmailVerification;

use Symfony\Component\HttpFoundation\Response;

class AuthSodiumDelegate {

    public function signInUrl()
    {
        return 'sign-in';
    }

    /**
     * Return the project's logo, either as a 
     * fully-qualified url, or a base-64 string.
     * Return null if no branding is desired.
     * @return string|null
     */
    public function logo()
    {
        return 'data:image/png;base64,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';
    }

    /**
     * Given a verification code, 
     *
     * @return \Illuminate\Http\Response|null
     */
    public function verifyEmail($code)
    {
        //@TODO validate the $code

        
        $emailVerification = EmailVerification::where('code', $code)->first();

        if ($emailVerification !== null) {
            $user = $emailVerification->user;
            $user->email = $emailVerification->email;
            $user->verified = now();

            $payload = (object) [
                'user' => $user,
                'logo' => $this->logo(),
                'signInUrl' => $this->signInUrl()
            ];

            return response()->view('authsodium::verify.success', compact('payload'), 200);

            // return view('authsodium::verify.success', compact('user'));



            
            $user->save();

            $emailVerification->delete();

            return $this->httpResponse(204, ['message' => 'email_verified_successfully']);
            
        } else {
            return $this->httpResponse(422);
        }
    }

    public function httpResponse($httpStatusCode, $extras = [])
    {
        $responseData = [
            'http_status_code' => $httpStatusCode,
            'http_status_message' => Response::$statusTexts[$httpStatusCode]
        ];

        if (sizeof($extras) > 0)
            $responseData = array_merge($responseData, $extras);

        return response()->json($responseData, $httpStatusCode);
    }

    /**
     * A user will be created, so validate their properties.
     *
     * @param ROTGP\AuthSodium\Models\AuthSodiumUser $user
     * @return void
     */
    public function emailVerificationWillBeCreated($emailVerification)
    {
        $existing = EmailVerification::where('user_id', $emailVerification->user_id)->get();
        if (count($existing) > 0)
            $existing->delete();

        $code = authSodium()->generateVerificationCode();
        while (EmailVerification::where('code', $code)->first() !== null)
            $code = authSodium()->generateVerificationCode();

        $emailVerification->code = $code;
    }


    /**
     * A user will be created, so validate their properties.
     *
     * @param ROTGP\AuthSodium\Models\AuthSodiumUser $user
     * @return void
     */
    public function userWillBeCreated($user)
    {
        if (!$this->validatePublicKeyFormat($user->public_key))
                throw new Exception('Invalid public key format');

        if (!$this->validateEmailFormat($user->email))
            throw new Exception('Invalid email format');
    }

    /**
     * A user was created, so create an email verification
     * and send a verification email.
     *
     * @param ROTGP\AuthSodium\Models\AuthSodiumUser $user
     * @return void
     */
    public function userWasCreated($user)
    {
        $emailVerification = new EmailVerification([
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        $emailVerification->save();

        if (app()->environment() !== 'testing')
            $this->sendVerificationEmail($user);
    }

    /**
     * A user will be updated. If the email address is being
     * updated, create an email verification
     * and send a verification email.
     *
     * @param ROTGP\AuthSodium\Models\AuthSodiumUser $user
     * @return void
     */
    public function userWillBeUpdated($user)
    {
        $changes = $user->getDirty();
        // dd('updating', $user->toArray(), $changes);
        // @TODO create an email verification and send a verification email.
    }
    
    /**
     * Return an instance of the user-defined User model.
     *
     * @return ROTGP\AuthSodium\Models\AuthSodiumUser
     */
    public function userModel()
    {
        $modelNS = config('authsodium.model');
        return new $modelNS;
    }

    /**
     * Generate a verification code of 32 characters.
     *
     * @return string
     */
    public function generateVerificationCode()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Send the verification email to the user's email address.
     *
     * @return void
     */
    public function sendVerificationEmail($user)
    {
        $routePrefix    = config('authsodium.prefix');
        if ($routePrefix !== null && $routePrefix !== '')
            $routePrefix = '/' . $routePrefix;

        $host           = request()->getHost();
        $to             = $user->email;
        $subject        = 'Please verify your account';
        $message        = 'Please visit ' . 'https://' . $host . $routePrefix . '/' . config('authsodium.routes.email_verification') . '?code=' . $user->emailVerification->code;
        $headers        = 'From: support@' . $host . "\r\n" .
            'Reply-To: support@' . $host . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        mail($to, $subject, $message, $headers);
    }

    /**
     * Return the host, ie: the domain of the 
     * application. Hard-coding this is probably
     * preferable.
     *
     * @return string
     */
    public function getHost()
    {
        return request()->getHost();
    }

    /**
     * Generate the full verification url
     *
     * @return string
     */
    public function getVerificationUrl()
    {
        return 'https://' . 
            $this->getHost() . 
            $this->getVerificationUrlStub();
    }

    /**
     * Generate the verification url stub, taking
     * into account the prefix, and verification
     * code.
     *
     * @return string
     */
    public function getVerificationUrlStub($emailVerification)
    {
        $code = $emailVerification->code;
        $routePrefix = config('authsodium.prefix');
        if (!empty($routePrefix))
            $routePrefix = '/' . $routePrefix;

        return $routePrefix . '/' . 
            config('authsodium.routes.email_verification') . 
            '?code=' . $code;
    }

    /**
     * Validate the public key format.
     *
     * @return boolean
     */
    public function validatePublicKeyFormat($value)
    {
        return $value !== null && 
            strlen($value) === 44 &&
            base64_encode(base64_decode($value, true)) === $value;
    }

    /**
     * Validate the public key format.
     *
     * @return boolean
     */
    public function validateEmailFormat($value)
    {
        return $value !== null && 
            strlen($value) >= 3 &&
            filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}