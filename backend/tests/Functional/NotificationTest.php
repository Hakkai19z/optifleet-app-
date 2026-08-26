<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * EF05 / EF07 — l'affectation d'un véhicule déclenche l'envoi d'un courriel au
 * conducteur. Le transport est `null://null` en test : aucun mail ne part
 * réellement, mais le collecteur de messages permet de vérifier l'envoi.
 */
class NotificationTest extends WebTestCase
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
    private function h(string $token): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];
    }

    public function testAffectationEnvoieUnCourrielAuConducteur(): void
    {
        $client = static::createClient();

        $gest = $this->login($client, 'gestionnaire@optifleet.fr', 'Gest@1234');
        $client->request('GET', '/api/gestionnaire/vehicules-disponibles', [], [], $this->h($gest));
        $vehicules = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($vehicules, 'Il faut un véhicule disponible dans les fixtures');
        $vehiculeId = $vehicules[0]['id'];

        $pierre = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');
        $client->request('GET', '/api/auth/me', [], [], $this->h($pierre));
        $conducteurId = json_decode($client->getResponse()->getContent(), true)['id'];

        $client->request('POST', '/api/gestionnaire/affecter', [], [], $this->h($gest), json_encode([
            'conducteurId' => $conducteurId,
            'vehiculeId' => $vehiculeId,
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertEmailCount(1);
    }
}
