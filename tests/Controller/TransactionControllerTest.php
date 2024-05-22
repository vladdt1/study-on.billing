<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TransactionControllerTest extends WebTestCase
{
    public function testGetTransactionsSuccess()
    {
        $client = static::createClient();

        // Авторизация пользователя для получения токена
        $client->request(
            'POST',
            '/api/v1/auth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'user@gmail.com',
                'password' => 'password'
            ])
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $token = $data['token'];

        // Запрос транзакций с авторизацией
        $client->request(
            'GET',
            '/api/v1/transactions',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $transactions = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($transactions);
    }

    public function testGetTransactionsUnauthorized()
    {
        $client = static::createClient();

        // Запрос транзакций без авторизации
        $client->request(
            'GET',
            '/api/v1/transactions',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ']
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }
}
