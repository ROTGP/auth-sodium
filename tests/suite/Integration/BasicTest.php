<?php
use ROTGP\AuthSodium\Test\IntegrationTestCase;
use ROTGP\AuthSodium\Test\Models\User;

class BasicTest extends IntegrationTestCase
{
    public function testTrue()
    {
        // $seed = sodium_crypto_generichash(1, null, 32);
        // $keyPair = sodium_crypto_sign_seed_keypair($seed);
        // $secretKey = sodium_crypto_sign_secretkey($keyPair);
        // $publicKey = sodium_crypto_sign_publickey($keyPair);

        // $message = 'Fede is the best';
        // $signature = sodium_crypto_sign_detached(
        //     $message, $secretKey
        // );

        // // $signature[0] = 1;

        // $signatureValid = sodium_crypto_sign_verify_detached($signature, $message, $publicKey);

        // dd(
        //     [
        //         'seed' => base64_encode($seed),
        //         'secretKey' => base64_encode($secretKey),
        //         'publicKey' => base64_encode($publicKey),
        //         'message' => $message,
        //         'signature' => base64_encode($signature),
        //         'signatureValid' => $signatureValid
        //     ]
        // );


        $queryData = [
            'b' => 'bee',
            'a' => 'ay'
        ];

        $postData = [
            'biography' => 'It was the best of times, it was the worst of times, it was the age of wisdom',
            'age' => 26,
            'Nationality' => 'Español',
            50 => 'fifty',
            49 => [1,30,50],
        ];
        
        $url = 'foos/3';

        $user = $this->users[0];

        $response = $this->request($user, 'put', $url,  $queryData, $postData, 1, 1);

        // $response->assertStatus(200);
        $json = $this->decodeResponse($response);
        dd('x?', $json, $response->getStatusCode(), $response);
       
        $this->assertTrue(false);
    }
}
