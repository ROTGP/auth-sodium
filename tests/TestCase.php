<?php

namespace ROTGP\AuthSodium\Test;

use ROTGP\AuthSodium\Test\Controllers\UserController;
use ROTGP\AuthSodium\Test\Controllers\AlbumController;
use ROTGP\AuthSodium\Test\Controllers\ArtistController;
use ROTGP\AuthSodium\Test\Controllers\PlayController;
use ROTGP\AuthSodium\Test\Controllers\SongController;
use ROTGP\AuthSodium\Test\Controllers\StreamingServiceController;

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
        
        // https://github.com/orchestral/testbench/issues/252
        $router = $app['router'];
        $router->resource('users', UserController::class);
    }
}
