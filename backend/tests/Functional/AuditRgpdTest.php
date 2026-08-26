<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * EF09 / A09 — journal d'audit ; et 9.f — anonymisation RGPD à la suppression.
 */
class AuditRgpdTest extends WebTestCase
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

    // Une tentative de connexion échouée laisse une trace, lisible par l'admin.
    public function testEchecConnexionEstJournalise(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'admin@optifleet.fr', 'motDePasse' => 'mauvais_mot_de_passe']));
        $this->assertResponseStatusCodeSame(401);

        $admin = $this->login($client, 'admin@optifleet.fr', 'Admin@1234');
        $client->request('GET', '/api/journal_audits', [], [], $this->h($admin));
        $this->assertResponseIsSuccessful();

        $actions = array_column(json_decode($client->getResponse()->getContent(), true), 'action');
        $this->assertContains('login_echec', $actions);
        $this->assertContains('login_reussi', $actions);
    }

    // Le journal d'audit est réservé à l'administrateur.
    public function testJournalInterditAuConducteur(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');

        $client->request('GET', '/api/journal_audits', [], [], $this->h($token));
        $this->assertResponseStatusCodeSame(403);
    }

    // La suppression de compte anonymise sans détruire, et rend le compte inutilisable.
    public function testSuppressionAnonymise(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'emma.petit@optifleet.fr', 'Cond@1234');

        $client->request('DELETE', '/api/auth/delete-account', [], [], $this->h($token));
        $this->assertResponseIsSuccessful();

        // Le compte anonymisé ne peut plus se connecter.
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => 'emma.petit@optifleet.fr', 'motDePasse' => 'Cond@1234']));
        $this->assertResponseStatusCodeSame(401);
    }
}
