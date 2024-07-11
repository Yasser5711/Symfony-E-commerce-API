<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testAddToCart()
    {
        $this->client->request('POST', '/api/carts', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'productId' => 1,
            'quantity' => 1
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testGetUserCart()
    {
        $this->client->request('GET', '/api/carts');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testValidateUserCart()
    {
        $this->client->request('PATCH', '/api/carts/validate');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testPayUserCart()
    {
        $this->client->request('PATCH', '/api/carts/pay/1');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testDeleteProductCart()
    {
        $this->client->request('DELETE', '/api/carts/1');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }
}
