<?php

/**
 * (c) Evis Bregu <evis.bregu@gmail.com>.
 */

namespace App\Tests\Controller\Api;

use App\Test\ApiTestCase;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use GuzzleHttp\Exception\GuzzleException;

class TokenControllerTest extends ApiTestCase
{
    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws GuzzleException
     * @throws \Exception
     */
    public function testPOSTCreateToken()
    {
        $this->createUser('filanfisteku', 'I<3Pizza');

        $client = static::createClient();

        $client->request(
            'post',
            '/api/tokens',
            [],
            [],
            [
                'HTTP_PHP_AUTH_USER' => 'filanfisteku@foo.com',
                'HTTP_PHP_AUTH_PW'   => 'I<3Pizza',
            ]
        );

        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $responseContent = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $responseContent);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Exception
     */
    public function testPOSTTokenInvalidCredentials()
    {
        $this->createUser('filanfisteku', 'I<3Pizza');

        $client = static::createClient();

        $client->request(
            'post',
            '/api/tokens',
            [],
            [],
            [
                'HTTP_PHP_AUTH_USER' => 'filanfisteku@foo.com',
                'HTTP_PHP_AUTH_PW'   => 'IH8Pizza',
            ]
        );

        $response = $client->getResponse();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Unauthorized');
        $this->asserter()->assertResponsePropertyEquals($response, 'detail', 'Invalid credentials.');
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testPOSTTokenInexistentUser()
    {
        $this->createUser('filanfisteku', 'I<3Pizza');

        $client = static::createClient();

        $client->request(
            'post',
            '/api/tokens',
            [],
            [],
            [
                'HTTP_PHP_AUTH_USER' => 'dummyuser@foo.com',
                'HTTP_PHP_AUTH_PW'   => 'dummypass',
            ]
        );

        $response = $client->getResponse();
        $this->assertSame(404, $response->getStatusCode());
    }

//    public function testBadToken()
//    {
//        $response = $this->client->post('/api/blog', [
//            'body'    => '[]',
//            'headers' => [
//                'Authorization' => 'Bearer WRONG',
//            ],
//        ]);
//        $this->assertSame(401, $response->getStatusCode());
//        $this->assertSame('application/problem+json', $response->getHeader('Content-Type')[0]);
//    }
}
