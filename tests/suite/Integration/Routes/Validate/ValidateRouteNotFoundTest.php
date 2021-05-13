<?php

use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Controllers\FooController;

class ValidateRouteNotFoundTest extends IntegrationTestCase
{
    /**
     * The route won't exist because by default the
     * route name is null
     */
    public function test_that_signed_request_to_validate_route_results_in_not_found()
    {
        $response = $this->signed()->request('get', 'validate')->response();
        $response->assertStatus(404);
    }
}
