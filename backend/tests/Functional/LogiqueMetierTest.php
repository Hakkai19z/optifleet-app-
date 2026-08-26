<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Cohérence de la logique métier :
 *  - unicité de l'affectation active d'un véhicule (pas de double affectation) ;
 *  - autorisation au niveau objet des véhicules via VehiculeVoter.
 */
class LogiqueMetierTest extends WebTestCase
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
    private function h(string $token, string $contentType = 'application/json'): array
    {
        return [
            'CONTENT_TYPE' => $contentType,
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];
    }

    private function json(KernelBrowser $client): array
    {
        return json_decode($client->getResponse()->getContent(), true);
    }

    // Réaffecter à Pierre un véhicule déjà affecté à Sophie doit clôturer
    // l'affectation de Sophie : un véhicule = un seul conducteur actif.
    public function testReaffectationLibereLeConducteurPrecedent(): void
    {
        $client = static::createClient();
        $gest = $this->login($client, 'gestionnaire@optifleet.fr', 'Gest@1234');

        // Le véhicule EF-456-GH (308) est affecté à Sophie dans les fixtures.
        $client->request('GET', '/api/gestionnaire/vue-flotte', [], [], $this->h($gest));
        $flotte = $this->json($client);
        $v308 = null;
        foreach ($flotte as $v) {
            if ('EF-456-GH' === $v['immatriculation']) {
                $v308 = $v;
                break;
            }
        }
        $this->assertNotNull($v308, 'Le véhicule 308 doit exister');
        $this->assertNotNull($v308['conducteur'], 'Le 308 doit être affecté (à Sophie) au départ');

        // Id de Pierre (conducteur@)
        $pierre = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');
        $client->request('GET', '/api/auth/me', [], [], $this->h($pierre));
        $pierreId = $this->json($client)['id'];

        // Réaffectation du 308 à Pierre
        $client->request('POST', '/api/gestionnaire/affecter', [], [], $this->h($gest), json_encode([
            'conducteurId' => $pierreId,
            'vehiculeId' => $v308['id'],
        ]));
        $this->assertResponseStatusCodeSame(201);

        // Sophie ne doit plus avoir le 308 (affectation clôturée)
        $sophie = $this->login($client, 'sophie.durand@optifleet.fr', 'Cond@1234');
        $client->request('GET', '/api/conducteur/mon-vehicule', [], [], $this->h($sophie));
        $this->assertNull($this->json($client)['vehicule'], 'Sophie ne doit plus avoir de véhicule affecté');

        // Pierre doit désormais conduire le 308
        $pierre = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');
        $client->request('GET', '/api/conducteur/mon-vehicule', [], [], $this->h($pierre));
        $this->assertSame('EF-456-GH', $this->json($client)['vehicule']['immatriculation']);
    }

    private function firstVehiculeId(KernelBrowser $client, string $token): int
    {
        $client->request('GET', '/api/vehicules', [], [], $this->h($token));

        return $this->json($client)[0]['id'];
    }

    // VehiculeVoter : un conducteur ne peut pas modifier un véhicule (EDIT refusé).
    public function testConducteurCannotEditVehicule(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');
        $id = $this->firstVehiculeId($client, $token);

        $client->request('PATCH', '/api/vehicules/' . $id, [], [], $this->h($token, 'application/merge-patch+json'), json_encode([
            'marque' => 'Piraté',
        ]));
        $this->assertResponseStatusCodeSame(403);
    }

    // VehiculeVoter : un gestionnaire peut modifier un véhicule (EDIT accordé).
    public function testGestionnaireCanEditVehicule(): void
    {
        $client = static::createClient();
        $token = $this->login($client, 'gestionnaire@optifleet.fr', 'Gest@1234');
        $id = $this->firstVehiculeId($client, $token);

        $client->request('PATCH', '/api/vehicules/' . $id, [], [], $this->h($token, 'application/merge-patch+json'), json_encode([
            'marque' => 'RenaultEdit',
        ]));
        $this->assertResponseIsSuccessful();
        $this->assertSame('RenaultEdit', $this->json($client)['marque']);
    }

    /** @return array{id:int, conducteur:?array} le 308 (affecté à Sophie dans les fixtures) */
    private function find308(KernelBrowser $client, string $gestToken): array
    {
        $client->request('GET', '/api/gestionnaire/vue-flotte', [], [], $this->h($gestToken));
        foreach ($this->json($client) as $v) {
            if ('EF-456-GH' === $v['immatriculation']) {
                return $v;
            }
        }
        $this->fail('Véhicule 308 introuvable');
    }

    // Point 1 : passer un véhicule affecté à « disponible » clôture son affectation.
    public function testChangerStatutDisponibleClotureAffectation(): void
    {
        $client = static::createClient();
        $gest = $this->login($client, 'gestionnaire@optifleet.fr', 'Gest@1234');
        $v308 = $this->find308($client, $gest);
        $this->assertNotNull($v308['conducteur'], 'Le 308 doit être affecté au départ');

        $client->request('PATCH', '/api/gestionnaire/changer-statut/' . $v308['id'], [], [], $this->h($gest), json_encode([
            'statut' => 'disponible',
        ]));
        $this->assertResponseIsSuccessful();

        // Sophie ne doit plus avoir de véhicule affecté
        $sophie = $this->login($client, 'sophie.durand@optifleet.fr', 'Cond@1234');
        $client->request('GET', '/api/conducteur/mon-vehicule', [], [], $this->h($sophie));
        $this->assertNull($this->json($client)['vehicule']);
    }

    // Point 2 : désaffecter deux fois la même affectation → la 2e est rejetée.
    public function testDesaffecterDejaClotureeRejetee(): void
    {
        $client = static::createClient();
        $gest = $this->login($client, 'gestionnaire@optifleet.fr', 'Gest@1234');
        $affectationId = $this->find308($client, $gest)['conducteur']['affectationId'];

        $client->request('DELETE', '/api/gestionnaire/desaffecter/' . $affectationId, [], [], $this->h($gest));
        $this->assertResponseIsSuccessful();

        // Deuxième appel : l'affectation est déjà clôturée
        $client->request('DELETE', '/api/gestionnaire/desaffecter/' . $affectationId, [], [], $this->h($gest));
        $this->assertResponseStatusCodeSame(400);
    }

    // Point 5 : un conducteur ne voit que les documents de ses véhicules.
    public function testDocumentsScopedToDriver(): void
    {
        $client = static::createClient();

        $admin = $this->login($client, 'admin@optifleet.fr', 'Admin@1234');
        $client->request('GET', '/api/documents', [], [], $this->h($admin));
        $adminIds = array_column($this->json($client), 'id');

        $pierre = $this->login($client, 'conducteur@optifleet.fr', 'Cond@1234');
        $client->request('GET', '/api/documents', [], [], $this->h($pierre));
        $pierreIds = array_column($this->json($client), 'id');

        $this->assertNotEmpty($pierreIds, 'Pierre conduit la Clio qui a des documents');
        $this->assertLessThan(count($adminIds), count($pierreIds), 'Le conducteur voit moins que toute la flotte');

        // Accès direct à un document hors périmètre → refusé
        $hidden = array_values(array_diff($adminIds, $pierreIds));
        $this->assertNotEmpty($hidden);
        $client->request('GET', '/api/documents/' . $hidden[0], [], [], $this->h($pierre));
        $this->assertResponseStatusCodeSame(403);
    }
}
