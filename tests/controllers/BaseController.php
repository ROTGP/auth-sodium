<?php

namespace ROTGP\AuthSodium\Test\Controllers;

use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    protected function getResponseStatusCode() : int
    {
        switch (request()->getMethod()) {
            case 'GET':
            case 'PUT':
                return 200;
            case 'POST':
                return 201;
            case 'DELETE':
                return 204;
            default:
                return request()->getMethod();
        }
    }

    public function respond($json) 
{
    return response()->json($json, $this->getResponseStatusCode());
}
}
