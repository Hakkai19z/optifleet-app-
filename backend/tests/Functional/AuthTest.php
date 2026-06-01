<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthTest extends WebTestCase
{
    public function testLoginSuccess(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'admin@optifleet.fr',
            'motDePasse' => 'Admin@1234',
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
    }

    public function testLoginEchec(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'admin@optifleet.fr',
            'motDePasse' => 'wrong_password',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAccessProtectedRouteWithoutToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/vehicules');
        $this->assertResponseStatusCodeSame(401);
    }
}
