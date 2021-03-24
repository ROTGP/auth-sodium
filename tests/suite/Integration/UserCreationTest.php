<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Models\User;
use ROTGP\AuthSodium\Models\EmailVerification;

use Carbon\Carbon;


class UserCreationTest extends IntegrationTestCase
{
    /**
     * Tests the basic flow for
     *  - creating a User
     *  - confirming existence of EmailVerification,
     *    as well as it's properties (user_id, etc)
     *  - visiting the verification url changes the
     * 
     *
     * @return void
     */
    public function testBasicUserFlow()
    {
        // grab the first user model
        $user = $this->users[0]['model'];

        // confirm the user's attributes
        $this->assertEquals('june.nader@maggio.com', $user['email'], );
        $this->assertEquals('zEFWj7UsCcH7xelwxW78eMy1OydIsjw1WqUkLm7Zd7w=', $user['public_key']);

        // visit the verification url
        $query = authSodium()->getVerificationUrlStub($user->emailVerification);
        $response = $this->get($query);

        $response->assertStatus(204);
        $response->assertNoContent();
        dd($response->getData()->message);
        $json = $this->decodeResponse($response);

        dd('mk??', $json);
        // 
        // $this->assertArrayHasKey('http_status_code', $json);
        // $this->assertEquals(403, $json['http_status_code']);
        // $this->assertArrayHasKey('http_status_message', $json);
        // $this->assertEquals('Forbidden', $json['http_status_message']);


        $json = $this->decodeResponse($response);
        // dd($response, 'no?');
        // $this->assertArrayHasKey('name', $json);

        dd($user->getVerificationUrl());

        // $emailVerification = EmailVerification::forEmail($user->email)->with(['user'])->get()->toArray();
        // dd('mk', config('authsodium.model'), User::all()->toArray(), $emailVerification);

        $this->assertTrue(true);
    }
}
