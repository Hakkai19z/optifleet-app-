<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * EF12 — Mise à jour du kilométrage par le conducteur sur son véhicule affecté.
 */
class KilometrageConducteurTest extends WebTestCase
{
    private function login(KernelBrowser $client, string $email, string $password): string
    {
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'motDePasse' => $password]));
        $this->assertResponseIsSuccessful();

        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    /** @return array<string, string> */
    private function headers(string $token): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];
    }

    // Pierre conduit la Clio (45000 km en fixtures) → mise à jour acceptée.
    public function testConducteurCanUpdateMileage(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');

        $client->request('PATCH', '/api/conducteur/kilometrage', [], [], $this->headers($token), json_encode([
            'kilometrage' => 46000,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertSame(46000, json_decode($client->getResponse()->getContent(), true)['kilometrage']);
    }

    // Le compteur ne peut pas reculer.
    public function testMileageCannotDecrease(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');

        $client->request('PATCH', '/api/conducteur/kilometrage', [], [], $this->headers($token), json_encode([
            'kilometrage' => 100,
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    // Valeur non entière rejetée.
    public function testMileageMustBeInteger(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');

        $client->request('PATCH', '/api/conducteur/kilometrage', [], [], $this->headers($token), json_encode([
            'kilometrage' => 'abc',
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    // Un conducteur sans véhicule affecté ne peut pas mettre à jour.
    public function testConducteurWithoutVehicleGets404(): void
    {
        $client = static::createClient();
        // Lucas a une réservation mais aucune affectation active.
        $token = $this->login($client, 'lucas.bernard@optifleet.fr', 'Cond@1234');

        $client->request('PATCH', '/api/conducteur/kilometrage', [], [], $this->headers($token), json_encode([
            'kilometrage' => 5000,
        ]));

        $this->assertResponseStatusCodeSame(404);
    }
}
