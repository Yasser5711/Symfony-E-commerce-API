<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testListProducts()
    {
        $this->client->request('GET', '/api/products');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testShowProduct()
    {
        $this->client->request('GET', '/api/products/1');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testAddProduct()
    {
        $this->client->request('POST', '/api/products', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'New Product',
            'description' => 'New Product Description',
            'photo' => 'https://path/to/image.png',
            'price' => 13.37
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testUpdateProduct()
    {
        $this->client->request('PUT', '/api/products/1', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'price' => 200
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testDeleteProduct()
    {
        $this->client->request('DELETE', '/api/products/1');

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
    }
}
