<?php

namespace App\DataFixtures;

use App\Entity\Affectation;
use App\Entity\Alerte;
use App\Entity\Categorie;
use App\Entity\Entretien;
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

        $manager->flush();
    }
}
