<?php

namespace ROTGP\AuthSodium\Test;

use ROTGP\AuthSodium\Test\Models\User;
use ROTGP\AuthSodium\Test\Models\Foo;
use ROTGP\AuthSodium\Test\Models\Bar;
use ROTGP\AuthSodium\Models\Nonce;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Routing\Route;

use Faker\Factory as Faker;

class IntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    private static $fake;
    protected $users = [];

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->buildUsers();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['ROTGP\AuthSodium\AuthSodiumServiceProvider'];
    }

    protected function tearDown(): void
    {
        Auth::logout();
        parent::tearDown();
    }

    public static function faker() {
        if (!isset(self::$fake)) self::$fake = Faker::create();
        return self::$fake;
    }

    protected function asUser($id)
    {
        return $this->actingAs(User::find($id));
    }

    protected function request($user, $method, $url, $queryData, $postData, $nonce, $timestamp)
    {
        $url = 'http://localhost/' . $url;

        $toSign = [];
        $toSign['method'] = strtolower($method);
        $toSign['url'] = strtolower($url);
        $toSign['nonce'] = $nonce;
        $toSign['timestamp'] = $timestamp;

        $query = '';
        if (sizeof($queryData) > 0) {
            $queryData = array_change_key_case($queryData, CASE_LOWER);
            ksort($queryData);
            $queryData = array_map(function($value) {
                return $value === null ? '' : $value;
            }, $queryData);
            $query = json_encode($queryData, JSON_UNESCAPED_UNICODE);
        } 
        $toSign['query_data'] = $query;

        $post = '';
        if (sizeof($postData) > 0) {
            $postData = array_map(function($value) {
                return $value === null ? '' : $value;
            }, $postData);
            $post = json_encode($postData, JSON_UNESCAPED_UNICODE);
        } 
        $toSign['post_data'] = $post;

        $toSign['user'] = $user['email'];
        
        $resource = $url;
        if (sizeof($queryData) > 0)
            $resource .= '?' . http_build_query($queryData);
        
        $toSign = implode('', array_values($toSign));

        $secretKey = base64_decode($user['secret_key']);

        $signature = base64_encode(
            sodium_crypto_sign_detached(
                $toSign, $secretKey
            )
        );

        $headers = [
            'Nonce' => $nonce,
            'Timestamp' => $timestamp,
            'Signature' => $signature,
            'User' => $user['email']
        ];

        return $this->withHeaders($headers)->{$method}($resource, $postData);
    }

    protected function decodeResponse($response)
    {
        return json_decode($response->getContent(), true);
    }

    protected function assertForbidden($response)
    {
        $response->assertStatus(403);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('http_status_code', $json);
        $this->assertEquals(403, $json['http_status_code']);
        $this->assertArrayHasKey('http_status_message', $json);
        $this->assertEquals('Forbidden', $json['http_status_message']);
    }

    protected function assertAssociativeArray($value)
    {
        $this->assertTrue($this->isAssociative($value));
    }

    protected function assertIndexedArray($value)
    {
        $this->assertFalse($this->isAssociative($value));
    }

    protected function isAssociative(array $value)
    {
        if (array() === $value) return false;
        return array_keys($value) !== range(0, count($value) - 1);
    }

    private function buildUsers()
    {
        self::faker()->seed(10);

        for ($i = 0; $i < 100; $i++) {
            
            $seed = sodium_crypto_generichash($i + 1, null, 32);
            $keyPair = sodium_crypto_sign_seed_keypair($seed);
            $secretKey = sodium_crypto_sign_secretkey($keyPair);
            $publicKey = sodium_crypto_sign_publickey($keyPair);
            
            $user = [];
            $user['name'] = self::faker()->name;
            $user['email'] = self::faker()->email;
            $user['secret_key'] = base64_encode($secretKey);
            $user['public_key'] = base64_encode($publicKey);
            
            $u = User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'public_key' => $user['public_key']
            ]);

            $user['id'] = $u->id;
            $this->users[] = $user;
        }

        for ($i = 0; $i < 10; $i++)
            Foo::create(['name' => self::faker()->name]);

        for ($i = 0; $i < 10; $i++)
            Bar::create(['name' => self::faker()->name]);

        // dd($this->users);

        // for ($i = 0; $i < 100; $i++) {
        //     $user = self::faker()->randomElement($this->users);
        //     $value = sodium_crypto_generichash($i, null, 32);
        //     $nonce = new Nonce([
        //         'value' => base64_encode($value),
        //     ]);
        //     $nonce->setUserKey($user['id']);
        //     $nonce->save();
        // }

        // dd('dne!', User::withCount(['nonces'])->get()->toArray());
        // dd(User::where('id', 2)->with(['nonces'])->get()->toArray());
        // dd(Nonce::forUserKey(3)->with(['authUser'])->get()->toArray());
    }
}
