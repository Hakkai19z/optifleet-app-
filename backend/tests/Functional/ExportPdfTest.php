<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * EF10 — export PDF des rapports (coûts et consommation).
 */
class ExportPdfTest extends WebTestCase
{
    private function login(KernelBrowser $client, string $email, string $password): string
    {
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'motDePasse' => $password]));
        $this->assertResponseIsSuccessful();

        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    public function testGestionnairePeutExporterLeRapportPdf(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'gestionnaire@optifleet.fr', 'Gest@1234');

        $client->request('GET', '/api/statistiques/export-pdf', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('application/pdf', $client->getResponse()->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $client->getResponse()->getContent());
    }

    public function testConducteurNePeutPasExporter(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');

        $client->request('GET', '/api/statistiques/export-pdf', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
