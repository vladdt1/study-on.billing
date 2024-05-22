<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CourseControllerTest extends WebTestCase
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

    public function testGetCourses()
    {
        [$client, $entityManager, $connection] = $this->beginTransaction();

        try {
            $client->request('GET', '/api/v1/courses');
            $response = $client->getResponse();
            
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $this->assertJson($response->getContent());
        } finally {
            $this->rollbackTransaction($connection);
        }
    }

    public function testGetCourse()
    {
        [$client, $entityManager, $connection] = $this->beginTransaction();

        try {
            // Предположим, что курс с кодом "code1" существует в базе данных
            $client->request('GET', '/api/v1/courses/code1');
            $response = $client->getResponse();

            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $this->assertJson($response->getContent());
        } finally {
            $this->rollbackTransaction($connection);
        }
    }

    public function testGetCourseNotFound()
    {
        [$client, $entityManager, $connection] = $this->beginTransaction();

        try {
            $client->request('GET', '/api/v1/courses/non_existing_code');
            $response = $client->getResponse();

            $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        } finally {
            $this->rollbackTransaction($connection);
        }
    }

    public function testCreateCourse()
    {
        [$client, $entityManager, $connection] = $this->beginTransaction();

        try {
            $client->request(
                'POST',
                '/api/v1/courses/create',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'code' => 'new_code',
                    'title' => 'New Course',
                    'description' => 'New course description',
                    'type' => 1,
                    'price' => 100
                ])
            );

            $response = $client->getResponse();
            $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
            $this->assertJson($response->getContent());
        } finally {
            $this->rollbackTransaction($connection);
        }
    }

    public function testUpdateCourse()
    {
        [$client, $entityManager, $connection] = $this->beginTransaction();

        try {
            // Предположим, что курс с кодом "code1" существует в базе данных
            $client->request(
                'POST',
                '/api/v1/courses/code2/update',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'code' => 'code2',
                    'title' => 'Updated Course',
                    'description' => 'Updated course description',
                    'type' => 1,
                    'price' => 200
                ])
            );

            $response = $client->getResponse();
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $this->assertJson($response->getContent());
        } finally {
            $this->rollbackTransaction($connection);
        }
    }

    public function testDeleteCourse()
    {
        [$client, $entityManager, $connection] = $this->beginTransaction();

        try {
            // Предположим, что курс с кодом "code1" существует в базе данных
            $client->request('DELETE', '/api/v1/courses/code2/delete');
            $response = $client->getResponse();

            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            $this->assertJson($response->getContent());
        } finally {
            $this->rollbackTransaction($connection);
        }
    }
}
