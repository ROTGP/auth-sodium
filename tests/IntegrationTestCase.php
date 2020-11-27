<?php

namespace ROTGP\AuthSodium\Test;

use ROTGP\AuthSodium\Test\Models\User;

use Illuminate\Support\Facades\Auth;
// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;

class IntegrationTestCase extends TestCase
{
    // use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->artisan('migrate:refresh', ['--database' => 'testbench']);
        // $this->refreshInMemoryDatabase(); 
    }

    protected function tearDown(): void
    {
        // $this->artisan('migrate:fresh', ['--database' => 'testbench']);
        // $this->resetController();
        Auth::logout();
        parent::tearDown();
    }

    protected function asUser($id)
    {
        return $this->actingAs(User::find($id));
    }

    protected function decodeResponse($response)
    {
        return json_decode($response->getContent(), true);
    }

    protected function resetController()
    {
        $this->app->get(Route::class)->controller = null;
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

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['ROTGP\AuthSodium\AuthSodiumServiceProvider'];
    }
}
