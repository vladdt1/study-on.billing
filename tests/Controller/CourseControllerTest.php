<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CourseControllerTest extends WebTestCase
{
    public function testGetCourses()
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/courses');
        $response = $client->getResponse();
            
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testGetCourse()
    {
        $client = static::createClient();

        // Предположим, что курс с кодом "code1" существует в базе данных
        $client->request('GET', '/api/v1/courses/code1');
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testGetCourseNotFound()
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/v1/courses/non_existing_code');
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testCreateCourse()
    {
        $client = static::createClient();

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
    }

    public function testUpdateCourse()
    {
        $client = static::createClient();

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
    }

    public function testDeleteCourse()
    {
        $client = static::createClient();

        // Предположим, что курс с кодом "code1" существует в базе данных
        $client->request('DELETE', '/api/v1/courses/code2/delete');
        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }
}
