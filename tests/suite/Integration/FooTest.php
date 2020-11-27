<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Models\User;
use Carbon\Carbon;

class FooTest extends IntegrationTestCase
{
    public function testFoo()
    {
        // dd(User::all()->toArray());
        $this->assertTrue(true);
    }
}
