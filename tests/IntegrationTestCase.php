<?php

namespace ROTGP\AuthSodium\Test;

use ROTGP\AuthSodium\Test\Models\User;
use Faker\Factory as Faker;


use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Route;

class IntegrationTestCase extends TestCase
{
    protected $faker;
    protected $users;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
        $this->artisan('migrate:refresh', ['--database' => 'testbench']);

        $this->app['config']->set('authsodium.model', User::class);
        
        $this->faker = Faker::create();
        $this->faker->seed(10);

        $this->generateUsers();

        // $this->app->config['authsodium.model'] = 'foo';
        // dd($this->app->config['authsodium']['model']);
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

    /**
     * For each test - generate an array of user
     * objects, each containing:
     *  - email
     *  - password
     *  - the user model object
     *
     * @return array
     */
    protected function generateUsers()
    {
        $this->users = [];
        for ($i = 0; $i < 10; $i++) {
            $user = [];
            $user['email'] = $this->faker->email;
            $user['password'] = $this->faker->password;
            $keyPair = sodium_crypto_sign_seed_keypair(sodium_crypto_generichash($user['email'] . $user['password']));
            $secretKey = base64_encode(sodium_crypto_sign_secretkey($keyPair));
            $publicKey = base64_encode(sodium_crypto_sign_publickey($keyPair));

            $model = new User(['email' => $user['email'], 'public_key' => $publicKey]);
            $model->save();
            $user['model'] = $model;
            $this->users[] = $user;
        }
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
