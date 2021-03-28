<?php

namespace ROTGP\AuthSodium\Test;

use ROTGP\AuthSodium\Test\Controllers\FooController;
use ROTGP\AuthSodium\Test\Controllers\BarController;
use ROTGP\AuthSodium\Test\Models\User;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('authsodium.model', User::class);
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
        
        $router = $app['router'];
        $router->resource('foos', FooController::class);
        $router->resource('bars', BarController::class);
    }
}
