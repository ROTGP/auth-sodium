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
use Illuminate\Contracts\Http\Kernel;

use Faker\Factory as Faker;

class IntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    private static $fake;
    protected $users = [];

    private $method = null;
    private $url = null;
    private $nonce = null;
    private $timestamp = null;
    private $user = null;
    private $glue = '';
    private $signatureString = null;
    private $signature = null;
    private $headers = null;
    private $resource = null;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->buildUsers();
        $this->cleanupRequestData();
    }

    protected function cleanupRequestData()
    {
        $this->method = 
            $this->url = 
            $this->nonce = 
            $this->timestamp = 
            $this->user = 
            $this->signatureString = 
            $this->signature =
            $this->headers = 
            $this->resource =
            null;
        $this->glue = '';
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

    protected function router()
    {
        return app()['router'];
    }

    protected function kernel()
    {
        return resolve(Kernel::class);
    }

    protected function request(
        $method = 'get',
        $url = 'foos',
        $queryData = null,
        $postData = null,
        $user = null,
        $nonce = 1,
        $timestamp = 1
        )
    {
        return $this->method($method)
            ->url($url)
            ->queryData($queryData)
            ->postData($postData)
            ->user($user)
            ->nonce($nonce)
            ->timestamp($timestamp);
        
       

        $headers = [
            'Nonce' => $nonce,
            'Timestamp' => $timestamp,
            'Signature' => $signature,
            'User-Identifier' => $user['email']
        ];

        // dd($method, $resource, $headers, $postData);
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

    protected function assertBadRequest($response)
    {
        $response->assertStatus(400);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('http_status_code', $json);
        // $this->assertEquals(400, $json['http_status_code']);
        // $this->assertArrayHasKey('http_status_message', $json);
        // $this->assertEquals('Forbidden', $json['http_status_message']);
    }

    protected function assertSuccessfulRequest($response)
    {
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);
        $this->assertCount(10, $json);
        $this->assertEquals('Kallie Langosh', $json[0]['name']);
        $this->assertEquals('Rex Lemke DVM', $json[9]['name']);
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

    protected function buildUsers()
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
            $user['model'] = User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'public_key' => $user['public_key']
            ]);
            $this->users[] = $user;
        }

        for ($i = 0; $i < 10; $i++)
            Foo::create(['name' => self::faker()->name]);

        for ($i = 0; $i < 10; $i++)
            Bar::create(['name' => self::faker()->name]);
    }

    public function response($signed = true)
    {
        if (!$signed)
            return $this->{$this->method}($this->getFullUrl(), $this->getPostData());
            
        return $this->withHeaders($this->getHeaders())->{$this->method}($this->getFullUrl(), $this->getPostData());
    }
    

    public function getFullUrl($withQuery = true)
    {
        $resource = $this->baseUrl . '/' . $this->url;
        
        if ($this->resource !== null)
            $resource .= '/' . $this->resource;

        if ($withQuery && $this->queryData !== null && sizeof($this->queryData) > 0)
            $resource .= '?' . http_build_query($this->queryData);
            
        return $resource;
    }

    public function getQueryParams()
    {
        return $this->queryParams();
    }

    public function getPostData()
    {
        return in_array($this->method, ['put', 'post']) ? $this->postData : [];
    }

    

    public function getQueryString()
    {
        $query = '';
        if ($this->queryData !== null && sizeof($this->queryData) > 0) {
            $this->queryData = array_change_key_case($this->queryData, CASE_LOWER);
            ksort($this->queryData);
            $this->queryData = array_map(function($value) {
                return $value === null ? '' : $value;
            }, $this->queryData);
            $query = json_encode($this->queryData, JSON_UNESCAPED_UNICODE);
        }
        return $query;
    }

    public function getPostString()
    {
        $post = '';
        if (in_array($this->method, ['put', 'post']) && $this->postData !== null && sizeof($this->postData) > 0) {
            $this->postData = array_map(function($value) {
                return $value === null ? '' : $value;
            }, $this->postData);
            $post = json_encode($this->postData, JSON_UNESCAPED_UNICODE);
        } 
        return $post;
    }

    public function getSignatureParams()
    {
        $params = [];
        $params['method'] = $this->method;
        $params['url'] = $this->getFullUrl(false);
        $params['query'] = $this->getQueryString();
        $params['post'] = $this->getPostString();
        $params['user'] = optional($this->user)['email'] ?? '';
        $params['nonce'] = $this->nonce;
        $params['timestamp'] = $this->timestamp;
        return $params;
    }

    public function getSignatureString()
    {
        return $this->signatureString ?? implode($this->glue, array_values($this->getSignatureParams()));
    }

    public function getSignature()
    {
        return $this->signature ?? ($this->user ? base64_encode(
            sodium_crypto_sign_detached(
                $this->getSignatureString(), base64_decode($this->user['secret_key'])
            )
        ) : '');
    }

    public function getHeaders()
    {
        return $this->headers ?? [
            'Auth-Nonce' => $this->nonce,
            'Auth-Timestamp' => $this->timestamp,
            'Auth-User' => optional($this->user)['email'] ?? '',
            'Auth-Signature' => $this->getSignature()
        ];
    }

    // SETTERS

    public function method($value)
    {
        $this->method = strtolower($value);
        return $this;
    }

    public function url($value)
    {
        $this->url = $value;
        return $this;
    }

    public function queryData($value)
    {
        if ($value === null) {
            $value = [
                'b' => 'banana',
                'a' => 'apple',
                'c' => 'carrot'
            ];
        }
        $this->queryData = $value;
        return $this;
    }

    public function postData($value)
    {
        if ($value === null) {
            $value = [
                'b' => 'banana',
                'a' => 'apple',
                'c' => 'carrot'
            ];
        }
        $this->postData = $value;
        return $this;
    }

    public function user($value)
    {
        if ($value === null)
            $value = $this->users[1];
        $this->user = $value;
        return $this;
    }

    public function nonce($value)
    {
        $this->nonce = $value;
        return $this;
    }

    public function timestamp($value)
    {
        $this->timestamp = $value;
        return $this;
    }

    public function glue($value)
    {
        $this->glue = $value;
        return $this;
    }

    public function signatureString($value)
    {
        $this->signatureString = $value;
        return $this;
    }

    public function signature($value)
    {
        $this->signature = $value;
        return $this;
    }

    public function headers($value)
    {
        $this->headers = $value;
        return $this;
    }

    public function resource($value)
    {
        $this->resource = $value;
        return $this;
    }
}
