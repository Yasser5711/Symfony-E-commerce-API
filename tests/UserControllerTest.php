<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testRegister()
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'testuser@example.com',
            'password' => 'password',
            'firstname' => 'Test',
            'lastname' => 'User'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testLogin()
    {
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'admin@admin.com',
            'password' => '0000'
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);

        return $data['token'];
    }

    /**
     * @depends testLogin
     */
    public function testGetUser(string $token)
    {
        $this->client->request('GET', '/api/get-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    /**
     * @depends testLogin
     */
    public function testUpdateUser(string $token)
    {
        $this->client->request('POST', '/api/get-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'admin@admin.com',
            'password' => '0000',
            'firstname' => 'UpdatedFirstName',
            'lastname' => 'UpdatedLastName'
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    /**
     * @depends testLogin
     */
    public function testDeleteUser(string $token)
    {
        $this->client->request('DELETE', '/api/get-user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    /**
     * @depends testLogin
     */
    public function testLogout(string $token)
    {
        $this->client->request('POST', '/api/logout', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    /**
     * @depends testLogin
     */
    public function testGetAllUsers(string $token)
    {
        $this->client->request('GET', '/api/get-all-users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    /**
     * @depends testLogin
     */
    public function testUpdateUserRole(string $token)
    {
        $this->client->request('POST', '/api/update-user-role', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'testuser@example.com',
            'role' => 'ROLE_ADMIN'
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }
}
