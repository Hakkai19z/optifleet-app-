<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests de non-régression des corrections de sécurité de l'audit :
 *  - élévation de privilèges via le champ `role` (bloquée) ;
 *  - mot de passe stocké en clair via API Platform (haché) ;
 *  - IDOR en lecture sur Reservation et Plein (cloisonnement par conducteur).
 */
class SecurityFixesTest extends WebTestCase
{
    private function login(KernelBrowser $client, string $email, string $password): string
    {
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'motDePasse' => $password]));

        $this->assertResponseIsSuccessful();

        return json_decode($client->getResponse()->getContent(), true)['token'];
    }

    /**
     * @return array<string, string>
     */
    private function bearer(string $token): array
    {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];
    }

    // 1. Un conducteur ne peut PAS s'auto-promouvoir ADMIN via PUT sur son profil.
    public function testConducteurCannotEscalatePrivilegesViaRole(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');

        // Récupère son id via /api/auth/me
        $client->request('GET', '/api/auth/me', [], [], $this->bearer($token));
        $me = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('CONDUCTEUR', $me['role']);
        $id = $me['id'];

        // Tentative d'élévation : on injecte role = ADMIN dans la mise à jour du profil
        $client->request('PUT', '/api/utilisateurs/' . $id, [], [], $this->bearer($token), json_encode([
            'nom' => 'Martin',
            'prenom' => 'Pierre',
            'email' => 'conducteur@optifleet.fr',
            'role' => 'ADMIN',
        ]));

        // La requête aboutit (édition de profil autorisée) mais le rôle est ignoré
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/api/auth/me', [], [], $this->bearer($token));
        $after = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('CONDUCTEUR', $after['role'], 'Le rôle ne doit jamais pouvoir être élevé par le conducteur lui-même');
    }

    // 2. Création d'un utilisateur via l'API : le mot de passe est haché (login possible).
    public function testAdminCreatedUserPasswordIsHashed(): void
    {
        $client = static::createClient();
        $adminToken = $this->login($client, 'admin@optifleet.fr', 'Admin@1234');

        $email = 'nouveau.' . uniqid() . '@optifleet.fr';
        $client->request('POST', '/api/utilisateurs', [], [], $this->bearer($adminToken), json_encode([
            'nom' => 'Test',
            'prenom' => 'Securite',
            'email' => $email,
            'plainMotDePasse' => 'MotDePasse1',
            'role' => 'CONDUCTEUR',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $body = json_decode($client->getResponse()->getContent(), true);
        // Le mot de passe (haché ou en clair) ne doit jamais être exposé
        $this->assertArrayNotHasKey('motDePasse', $body);
        $this->assertArrayNotHasKey('plainMotDePasse', $body);

        // Preuve du hachage : la connexion avec le mot de passe en clair fonctionne
        // (le hash bcrypt vérifie le mot de passe → il n'a pas été stocké en clair).
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => $email, 'motDePasse' => 'MotDePasse1']));
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', json_decode($client->getResponse()->getContent(), true));
    }

    // 3. IDOR Reservation : un conducteur ne peut pas lire la réservation d'un autre.
    public function testConducteurCannotReadOthersReservation(): void
    {
        $client = static::createClient();

        // L'admin liste toutes les réservations pour récupérer un id existant
        $adminToken = $this->login($client, 'admin@optifleet.fr', 'Admin@1234');
        $client->request('GET', '/api/reservations', [], [], $this->bearer($adminToken));
        $all = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($all, 'Les fixtures doivent contenir des réservations');
        $someReservationId = $all[0]['id'];

        // Pierre (conducteur@) n'a AUCUNE réservation dans les fixtures → accès refusé
        $pierreToken = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');
        $client->request('GET', '/api/reservations/' . $someReservationId, [], [], $this->bearer($pierreToken));
        $this->assertResponseStatusCodeSame(403, 'Un conducteur ne doit pas lire la réservation d\'autrui');
    }

    // 4. Cloisonnement Plein : la collection ne renvoie que les pleins des véhicules conduits.
    public function testPleinCollectionIsScopedToDriver(): void
    {
        $client = static::createClient();

        // Vue admin : tous les pleins de la flotte
        $adminToken = $this->login($client, 'admin@optifleet.fr', 'Admin@1234');
        $client->request('GET', '/api/pleins', [], [], $this->bearer($adminToken));
        $allPleins = json_decode($client->getResponse()->getContent(), true);
        $adminIds = array_column($allPleins, 'id');

        // Vue Pierre : uniquement les pleins de la Clio (son véhicule affecté)
        $pierreToken = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');
        $client->request('GET', '/api/pleins', [], [], $this->bearer($pierreToken));
        $pierrePleins = json_decode($client->getResponse()->getContent(), true);
        $pierreIds = array_column($pierrePleins, 'id');

        // Le conducteur voit strictement moins de pleins que l'admin (flotte cloisonnée)
        $this->assertLessThan(count($adminIds), count($pierreIds));
        $this->assertNotEmpty($pierreIds, 'Pierre conduit la Clio qui a des pleins');

        // 5. IDOR item Plein : un plein non visible en collection est aussi refusé en accès direct.
        $hidden = array_values(array_diff($adminIds, $pierreIds));
        $this->assertNotEmpty($hidden, 'Il doit exister des pleins hors périmètre de Pierre');
        $client->request('GET', '/api/pleins/' . $hidden[0], [], [], $this->bearer($pierreToken));
        $this->assertResponseStatusCodeSame(403, 'Accès direct à un plein hors périmètre → refusé');
    }
}
