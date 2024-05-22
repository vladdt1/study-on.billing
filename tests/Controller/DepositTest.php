<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DepositTest extends WebTestCase
{
    private function beginTransaction()
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();

        return [$client, $entityManager, $connection];
    }

    private function rollbackTransaction($connection)
    {
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }
    }

    public function testDepositFundsSuccess()
    {
        [$client, $entityManager, $connection] = $this->beginTransaction();

        try {
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

            // Запрос на пополнение баланса с авторизацией
            $client->request(
                'POST',
                '/api/v1/deposit',
                [],
                [],
                ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
                json_encode(['amount' => 100])
            );

            $response = $client->getResponse();
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $responseData = json_decode($response->getContent(), true);
            $this->assertTrue($responseData['success']);
            $this->assertGreaterThan(0, $responseData['balance']);
        } finally {
            $this->rollbackTransaction($connection);
        }
    }

    public function testDepositFundsUnauthorized()
    {
        [$client, $entityManager, $connection] = $this->beginTransaction();

        try {
            // Запрос на пополнение баланса без авторизации
            $client->request(
                'POST',
                '/api/v1/deposit',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['amount' => 100])
            );

            $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
        } finally {
            $this->rollbackTransaction($connection);
        }
    }

    public function testDepositFundsInvalidAmount()
    {
        [$client, $entityManager, $connection] = $this->beginTransaction();

        try {
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

            // Запрос на пополнение баланса с некорректной суммой
            $client->request(
                'POST',
                '/api/v1/deposit',
                [],
                [],
                ['HTTP_AUTHORIZATION' => 'Bearer ' . $token, 'CONTENT_TYPE' => 'application/json'],
                json_encode(['amount' => -100])
            );

            $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        } finally {
            $this->rollbackTransaction($connection);
        }
    }
}
