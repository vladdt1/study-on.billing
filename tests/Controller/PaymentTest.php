<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PaymentTest extends WebTestCase
{
    public function testPayForCourseSuccess()
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

        // Запрос на оплату курса с авторизацией
        $client->request(
            'POST',
            '/api/v1/courses/code1/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNotEmpty(json_decode($response->getContent(), true));
    }

    public function testPayForCourseUnauthorized()
    {
        $client = static::createClient();

        // Запрос на оплату курса без авторизации
        $client->request(
            'POST',
            '/api/v1/courses/code1/pay',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testPayForCourseNotFound()
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

        // Запрос на оплату несуществующего курса
        $client->request(
            'POST',
            '/api/v1/courses/non_existing_course/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testPayForCourseInsufficientFunds()
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
                'username' => 'newuser@gmail.com',
                'password' => 'password'
            ])
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $token = $data['token'];

        // Запрос на оплату курса с недостаточным балансом
        $client->request(
            'POST',
            '/api/v1/courses/code1/pay',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertEquals(Response::HTTP_PAYMENT_REQUIRED, $client->getResponse()->getStatusCode());
    }
}
