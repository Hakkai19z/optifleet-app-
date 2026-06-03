<?php

namespace App\DataFixtures;

use App\Entity\Affectation;
use App\Entity\Alerte;
use App\Entity\Categorie;
use App\Entity\Document;
use App\Entity\Entretien;
use App\Entity\Plein;
use App\Entity\Reservation;
use App\Entity\Utilisateur;
use App\Entity\Vehicule;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $admin = new Utilisateur();
        $admin->setNom('Admin');
        $admin->setPrenom('OptiFleet');
        $admin->setEmail('admin@optifleet.fr');
        $admin->setRole('ADMIN');
        $admin->setMotDePasse($this->passwordHasher->hashPassword($admin, 'Admin@1234'));
        $manager->persist($admin);

        $gestionnaire = new Utilisateur();
        $gestionnaire->setNom('Dupont');
        $gestionnaire->setPrenom('Marie');
        $gestionnaire->setEmail('gestionnaire@optifleet.fr');
        $gestionnaire->setRole('GESTIONNAIRE');
        $gestionnaire->setMotDePasse($this->passwordHasher->hashPassword($gestionnaire, 'Gest@1234'));
        $manager->persist($gestionnaire);

        $conducteur = new Utilisateur();
        $conducteur->setNom('Martin');
        $conducteur->setPrenom('Pierre');
        $conducteur->setEmail('conducteur@optifleet.fr');
        $conducteur->setRole('CONDUCTEUR');
        $conducteur->setMotDePasse($this->passwordHasher->hashPassword($conducteur, 'Cond@1234'));
        $manager->persist($conducteur);

        // Conducteurs supplémentaires pour la démo des affectations
        $autresConducteurs = [
            ['Durand', 'Sophie', 'sophie.durand@optifleet.fr'],
            ['Bernard', 'Lucas', 'lucas.bernard@optifleet.fr'],
            ['Petit', 'Emma', 'emma.petit@optifleet.fr'],
            ['Moreau', 'Hugo', 'hugo.moreau@optifleet.fr'],
        ];
        $conducteurEntities = [$conducteur];
        foreach ($autresConducteurs as [$nom, $prenom, $email]) {
            $c = new Utilisateur();
            $c->setNom($nom);
            $c->setPrenom($prenom);
            $c->setEmail($email);
            $c->setRole('CONDUCTEUR');
            $c->setMotDePasse($this->passwordHasher->hashPassword($c, 'Cond@1234'));
            $manager->persist($c);
            $conducteurEntities[] = $c;
        }

        $catBerline = new Categorie();
        $catBerline->setLibelle('Berline');
        $catBerline->setDescription('Voiture berline de fonction');
        $manager->persist($catBerline);

        $catUtilitaire = new Categorie();
        $catUtilitaire->setLibelle('Utilitaire');
        $catUtilitaire->setDescription('Véhicule utilitaire léger');
        $manager->persist($catUtilitaire);

        $catSUV = new Categorie();
        $catSUV->setLibelle('SUV');
        $catSUV->setDescription('Sport Utility Vehicle');
        $manager->persist($catSUV);

        // statut: en_mission = véhicule affecté à un conducteur ci-dessous
        $vehicules = [
            ['AB-123-CD', 'Renault', 'Clio',     2022, 45000,  'en_mission',  $catBerline,    50000,  '12 Avenue des Champs-Élysées, Paris'],
            ['EF-456-GH', 'Peugeot', '308',      2021, 62000,  'en_mission',  $catBerline,    60000,  '1 Place Bellecour, Lyon'],
            ['IJ-789-KL', 'Citroën', 'Berlingo', 2020, 89000,  'disponible',  $catUtilitaire, 100000, '20 Rue de la République, Marseille'],
            ['MN-012-OP', 'Ford',    'Transit',  2019, 110000, 'maintenance', $catUtilitaire, 120000, '5 Place du Capitole, Toulouse'],
            ['QR-345-ST', 'Toyota',  'RAV4',     2023, 15000,  'disponible',  $catSUV,        30000,  '3 Rue Nationale, Lille'],
        ];

        $vehiculeEntities = [];
        foreach ($vehicules as [$immat, $marque, $modele, $annee, $km, $statut, $cat, $quota, $adresse]) {
            $v = new Vehicule();
            $v->setImmatriculation($immat);
            $v->setMarque($marque);
            $v->setModele($modele);
            $v->setAnnee($annee);
            $v->setKilometrage($km);
            $v->setStatut($statut);
            $v->setCategorie($cat);
            $v->setQuotaKmAnnuel($quota);
            $v->setAdresse($adresse);
            $manager->persist($v);
            $vehiculeEntities[] = $v;
        }

        // Affectations actives : Pierre -> Clio, Sophie -> 308
        $affectationsInitiales = [
            [$conducteurEntities[0], $vehiculeEntities[0], 'Véhicule de fonction attribué'],
            [$conducteurEntities[1], $vehiculeEntities[1], 'Mission commerciale région Rhône-Alpes'],
        ];
        foreach ($affectationsInitiales as [$cond, $veh, $comment]) {
            $aff = new Affectation();
            $aff->setConducteur($cond);
            $aff->setVehicule($veh);
            $aff->setDateDebut(new \DateTime('-2 weeks'));
            $aff->setCommentaire($comment);
            $manager->persist($aff);
        }

        $entretien = new Entretien();
        $entretien->setVehicule($vehiculeEntities[0]);
        $entretien->setType('vidange');
        $entretien->setDateRealise(new \DateTime('-3 months'));
        $entretien->setDateProchaine(new \DateTime('-1 week'));
        $entretien->setKmProchaine(50000);
        $entretien->setCout('120.00');
        $manager->persist($entretien);

        $alerte = new Alerte();
        $alerte->setVehicule($vehiculeEntities[0]);
        $alerte->setType('vidange');
        $alerte->setMessage('Vidange échue pour Renault Clio (AB-123-CD)');
        $alerte->setDateEcheance(new \DateTime('-1 week'));
        $alerte->setStatut('en_attente');
        $manager->persist($alerte);

        // Pleins de carburant — historique sur la Clio (km croissants pour le calcul de conso)
        $pleinsClio = [
            ['-90 days', 42.5, 1.812, 45200, 'diesel'],
            ['-60 days', 45.0, 1.795, 45850, 'diesel'],
            ['-30 days', 40.8, 1.842, 46500, 'diesel'],
            ['-7 days',  44.2, 1.875, 47100, 'diesel'],
        ];
        foreach ($pleinsClio as [$quand, $litres, $prix, $km, $type]) {
            $plein = new Plein();
            $plein->setVehicule($vehiculeEntities[0]);
            $plein->setDate(new \DateTime($quand));
            $plein->setLitres((string) $litres);
            $plein->setPrixLitre((string) $prix);
            $plein->setKilometrage($km);
            $plein->setTypeCarburant($type);
            $manager->persist($plein);
        }
        // Quelques pleins sur le RAV4 (essence)
        foreach ([['-40 days', 50.0, 1.932, 14200], ['-12 days', 48.5, 1.958, 14900]] as [$quand, $litres, $prix, $km]) {
            $plein = new Plein();
            $plein->setVehicule($vehiculeEntities[4]);
            $plein->setDate(new \DateTime($quand));
            $plein->setLitres((string) $litres);
            $plein->setPrixLitre((string) $prix);
            $plein->setKilometrage($km);
            $plein->setTypeCarburant('essence');
            $manager->persist($plein);
        }

        // Réservations à venir sur les véhicules disponibles
        $reservations = [
            [$vehiculeEntities[2], $conducteurEntities[2], '+3 days 09:00', '+5 days 18:00', 'confirmee', 'Déménagement matériel salon'],
            [$vehiculeEntities[4], $conducteurEntities[3], '+1 day 08:00',  '+1 day 17:00',  'en_attente', 'Visite client Grenoble'],
            [$vehiculeEntities[2], $conducteurEntities[1], '+10 days 07:00', '+12 days 19:00', 'confirmee', 'Salon professionnel Marseille'],
        ];
        foreach ($reservations as [$veh, $cond, $debut, $fin, $statut, $motif]) {
            $resa = new Reservation();
            $resa->setVehicule($veh);
            $resa->setConducteur($cond);
            $resa->setDateDebut(new \DateTime($debut));
            $resa->setDateFin(new \DateTime($fin));
            $resa->setStatut($statut);
            $resa->setMotif($motif);
            $manager->persist($resa);
        }

        // Documents administratifs — certains expirés/expirant bientôt pour la démo des échéances
        $documents = [
            [$vehiculeEntities[0], 'assurance',          'AXA-2024-88123',  '-300 days', '+65 days'],
            [$vehiculeEntities[0], 'controle_technique', 'CT-2024-0042',    '-200 days', '-5 days'],
            [$vehiculeEntities[1], 'assurance',          'MAIF-2024-11920', '-180 days', '+20 days'],
            [$vehiculeEntities[3], 'controle_technique', 'CT-2025-0510',    '-90 days',  '+10 days'],
            [$vehiculeEntities[4], 'carte_grise',        'QR345ST-FR',      '-700 days', '+400 days'],
        ];
        foreach ($documents as [$veh, $type, $numero, $delivrance, $expiration]) {
            $doc = new Document();
            $doc->setVehicule($veh);
            $doc->setType($type);
            $doc->setNumero($numero);
            $doc->setDateDelivrance(new \DateTime($delivrance));
            $doc->setDateExpiration(new \DateTime($expiration));
            $manager->persist($doc);
        }

        $manager->flush();
    }
}
