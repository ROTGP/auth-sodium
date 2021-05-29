<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;

class SingletonTest extends IntegrationTestCase
{
    public function test_that_calls_to_auth_sodium_delegate_returns_a_singleton()
    {
        $one = authSodium();
        $two = authSodium();
        $one->foo = 1;
        $two->foo = 2;
        $this->assertEquals(2, $one->foo);
        $this->assertEquals(2, $two->foo);
        $this->assertTrue($one === $two);
    }
}
