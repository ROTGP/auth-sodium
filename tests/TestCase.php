<?php

namespace ROTGP\AuthSodium\Test;

use ROTGP\AuthSodium\Test\Controllers\FooController;
use ROTGP\AuthSodium\Test\Controllers\BarController;
use ROTGP\AuthSodium\Test\Models\User;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    // protected $preserveGlobalState = false;
    protected $runTestInSeparateProcess = true;

    protected function customizeSetup()
    {
        //
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('authsodium.user.model', User::class);
        $app['config']->set('app.key', 'base64:A8UAQXCblckPdjeR9+f6xr+oZeu/U0wHgHhz6T1ayvw=');
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'host' => '127.0.0.1',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer'
        ]);
        $this->customizeSetup();
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
