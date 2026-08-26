<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Comble l'angle mort n° 3 du chapitre X : un test qui vérifie la configuration
 * de sécurité elle-même. Ici, la présence des en-têtes de durcissement posés par
 * SecurityHeadersListener sur toute réponse de l'API.
 */
class SecurityHeadersTest extends WebTestCase
{
    public function testEntetesDeSecuritePresents(): void
    {
        $client = static::createClient();

        // Une réponse quelconque de l'API suffit : les en-têtes sont posés sur toutes.
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'inexistant@optifleet.fr', 'motDePasse' => 'x']));

        $headers = $client->getResponse()->headers;

        $this->assertSame('DENY', $headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertSame('no-referrer', $headers->get('Referrer-Policy'));
        $this->assertSame('same-origin', $headers->get('Cross-Origin-Opener-Policy'));
        $this->assertNotNull($headers->get('Content-Security-Policy'));
    }
}
