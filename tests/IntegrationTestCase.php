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
use Carbon\Carbon;
use Event;
use AuthSodium;
use Mockery\MockInterface;

abstract class IntegrationTestCase extends TestCase
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
    private $signed = false;
    protected $events = [];

    protected $epoch;

    protected $mock;

    protected $shouldMock = true;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setTime();
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->buildUsers();
        $this->cleanupRequestData();
        $this->events = [];
        Event::listen('Illuminate\Auth\Events\*', function ($value, $event) {
            $this->events[] = $event[0];
        });
        $this->assertUserLoggedOut();

        if (!$this->shouldMock) {
            return;
        }
        $this->resetMock();
        $this->mockTime();
    }

    protected function resetMock()
    {
        $this->mock = $this->partialMock(config('authsodium.delegate'), function (MockInterface $mock) {
            return $mock;
        });
        return $this->mock;
    }

    protected function mockTime()
    {
        $getSystemTime = function() {
            return config('authsodium.timestamp.milliseconds', true) ? 
                intval(Carbon::now()->getPreciseTimestamp(3)) : 
                Carbon::now()->getTimestamp();
        };
        
        $this->mock->shouldReceive('getSystemTime')->andReturnUsing($getSystemTime);
    }

    protected function setTime()
    {
        $this->epoch = Carbon::createFromFormat(
            'd/m/Y H:i:s',
            '17/03/2021 18:55:20', // St Patrick's Day
            'UTC'
        );
        Carbon::setTestNow($this->epoch);
    }

    protected function setTimestampToNow($offset = 0)
    {
        $timestamp = config('authsodium.timestamp.milliseconds', true) ?
            intval(microtime(true) * 1000) : time();
        $this->timestamp($timestamp + $offset);
    }

    protected function setTestNow($value, $updateTimestamp = true)
    {
        Carbon::setTestNow($value);

        if ($updateTimestamp) {
            $this->setTimestampFromDate($value);
        }
    }

    protected function setTimestampFromDate($value)
    {
        if (config('authsodium.timestamp.milliseconds', true)) {
            $this->timestamp(intval($value->getPreciseTimestamp(3)));
        }  else {
            $this->timestamp($value->getTimestamp());
        }
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
        $this->signed = false;
    }

    protected function tearDown(): void
    {
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
        $timestamp = null
        )
    {
        if (!$timestamp) {
            $timestamp = intval($this->epoch->getPreciseTimestamp(3));
        }
        return $this->method($method)
            ->url($url)
            ->queryData($queryData)
            ->postData($postData)
            ->user($user)
            ->nonce($nonce)
            ->timestamp($timestamp);
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
    }

    protected function assertUnauthorized($response)
    {
        $response->assertStatus(401);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('http_status_code', $json);
        $this->assertEquals(401, $json['http_status_code']);
        $this->assertArrayHasKey('http_status_message', $json);
        $this->assertEquals("Unauthorized", $json['http_status_message']);
    }

    protected function assertValidationError($response, $error)
    {
        $response->assertStatus(422);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('http_status_code', $json);
        $this->assertEquals(422, $json['http_status_code']);
        $this->assertArrayHasKey('http_status_message', $json);
        $this->assertEquals('Unprocessable Entity', $json['http_status_message']);
        $this->assertArrayHasKey('error_key', $json);
        $this->assertEquals($error, $json['error_key']);
    }

    protected function assertInternalServerError($response)
    {
        $response->assertStatus(500);
    }

    protected function assertSuccessfulRequest($response)
    {
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);
        $this->assertCount(10, $json);
        $this->assertEquals('Kallie Langosh', $json[0]['name']);
        $this->assertEquals('Rex Lemke DVM', $json[9]['name']);
    }

    protected function assertUserLoggedIn($user = null)
    {
        if (!$user) {
            $user = $this->users[0]['model'];
        }
        $this->assertTrue($user->is(authSodium()->getUser()));
    }

    protected function assertUserLoggedOut()
    {
        $this->assertNull(authSodium()->getUser());
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
                'public_key' => $user['public_key'],
                'enabled' => true
            ]);
            $this->users[] = $user;
        }

        for ($i = 0; $i < 10; $i++)
            Foo::create(['name' => self::faker()->name, 'user_id' => 0]);

        for ($i = 0; $i < 10; $i++)
            Bar::create(['name' => self::faker()->name, 'user_id' => 0]);
    }

    public function response()
    {
        if (!$this->signed)
            return $this->{$this->method}($this->getFullUrl(), $this->getPostData());
            
        return $this->withHeaders($this->getHeaders())->{$this->method}($this->getFullUrl(), $this->getPostData());
    }
    

    public function getFullUrl($withQuery = true)
    {
        $resource = $this->baseUrl . '/' . $this->url;
        
        if ($this->resource)
            $resource .= '/' . $this->resource;

        if ($withQuery && $this->queryData && sizeof($this->queryData) > 0)
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
        if (!empty($this->queryData)) {
            $this->queryData = array_change_key_case($this->queryData, CASE_LOWER);
            ksort($this->queryData);
            $this->queryData = array_map(function($value) {
                return $value === null ? '' : $value;
            }, $this->queryData);
            $query = json_encode($this->queryData, JSON_UNESCAPED_UNICODE);
        }
        return $query;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getPostString()
    {
        $post = '';
        if (in_array($this->method, ['put', 'post']) && !empty($this->postData)) {
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
        $params['timestamp'] = $this->getTimestamp();
        $params['nonce'] = $this->nonce;
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
            'Auth-Timestamp' => $this->getTimestamp(),
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
                'name' => 'Jim'
            ];
        }
        $this->postData = $value;
        return $this;
    }

    public function user($value)
    {
        if ($value === null)
            $value = $this->users[0];
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

    public function signed()
    {
        $this->signed = true;
        return $this;
    }

    public function unsigned()
    {
        $this->signed = false;
        return $this;
    }
}
