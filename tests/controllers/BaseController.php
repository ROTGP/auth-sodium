<?php

namespace ROTGP\AuthSodium\Test\Controllers;

use Illuminate\Routing\Controller;

use Symfony\Component\HttpFoundation\Response;

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

    protected function respond($json) 
    {
        return response()->json($json, $this->getResponseStatusCode());
    }

    protected function errorResponse(int $httpStatusCode = 400, $extras = []) : void
    {
        $responseData = [
            'http_status_code' => $httpStatusCode,
            'http_status_message' => Response::$statusTexts[$httpStatusCode]
        ];

        if (sizeof($extras) > 0) {
            $responseData = array_merge($responseData, $extras);
        }

        abort(response()->json($responseData, $httpStatusCode));
    }

    protected function validationErrorResponse($errorMessages = []) : void
    {
        $httpStatusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

        $responseData = [
            'http_status_code' => $httpStatusCode,
            'http_status_message' => Response::$statusTexts[$httpStatusCode]
        ];

        if (sizeof($errorMessages) > 0) {
            $responseData = array_merge($responseData, $errorMessages);
        }

        abort(response()->json($responseData, $httpStatusCode));
    }
}
