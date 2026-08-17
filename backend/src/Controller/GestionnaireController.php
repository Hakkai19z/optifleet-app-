<?php

namespace App\Controller;

use App\Entity\Affectation;
use App\Entity\Utilisateur;
use App\Entity\Vehicule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/gestionnaire')]
#[IsGranted('ROLE_GESTIONNAIRE')]
class GestionnaireController extends AbstractController
{
    #[Route('/conducteurs', name: 'gestionnaire_conducteurs', methods: ['GET'])]
    public function listeConducteurs(EntityManagerInterface $em): JsonResponse
    {
        $conducteurs = $em->getRepository(Utilisateur::class)
            ->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', 'CONDUCTEUR')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($conducteurs as $c) {
            $affectationActive = $em->getRepository(Affectation::class)
                ->createQueryBuilder('a')
                ->leftJoin('a.vehicule', 'v')->addSelect('v')
                ->where('a.conducteur = :conducteur')
                ->andWhere('a.dateFin IS NULL OR a.dateFin > :now')
                ->setParameter('conducteur', $c)
                ->setParameter('now', new \DateTime())
                ->orderBy('a.dateDebut', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $result[] = [
                'id' => $c->getId(),
                'nom' => $c->getNom(),
                'prenom' => $c->getPrenom(),
                'email' => $c->getEmail(),
                'vehiculeActuel' => $affectationActive ? [
                    'id' => $affectationActive->getVehicule()->getId(),
                    'immatriculation' => $affectationActive->getVehicule()->getImmatriculation(),
                    'marque' => $affectationActive->getVehicule()->getMarque(),
                    'modele' => $affectationActive->getVehicule()->getModele(),
                    'affectationId' => $affectationActive->getId(),
                    'dateDebut' => $affectationActive->getDateDebut()?->format('d/m/Y'),
                ] : null,
            ];
        }

        return $this->json($result);
    }

    #[Route('/vehicules-disponibles', name: 'gestionnaire_vehicules_disponibles', methods: ['GET'])]
    public function vehiculesDisponibles(EntityManagerInterface $em): JsonResponse
    {
        $vehicules = $em->getRepository(Vehicule::class)
            ->createQueryBuilder('v')
            ->leftJoin('v.categorie', 'c')->addSelect('c')
            ->where('v.statut = :statut')
            ->setParameter('statut', 'disponible')
            ->orderBy('v.immatriculation', 'ASC')
            ->getQuery()
            ->getResult();

        $result = array_map(fn ($v) => [
            'id' => $v->getId(),
            'immatriculation' => $v->getImmatriculation(),
            'marque' => $v->getMarque(),
            'modele' => $v->getModele(),
            'annee' => $v->getAnnee(),
            'kilometrage' => $v->getKilometrage(),
            'categorie' => $v->getCategorie()?->getLibelle(),
        ], $vehicules);

        return $this->json($result);
    }

    #[Route('/affecter', name: 'gestionnaire_affecter', methods: ['POST'])]
    public function affecterVehicule(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $conducteurId = $data['conducteurId'] ?? null;
        $vehiculeId = $data['vehiculeId'] ?? null;
        $commentaire = $data['commentaire'] ?? null;

        if (! $conducteurId || ! $vehiculeId) {
            return $this->json(['message' => 'conducteurId et vehiculeId sont requis'], Response::HTTP_BAD_REQUEST);
        }

        $conducteur = $em->getRepository(Utilisateur::class)->find($conducteurId);
        $vehicule = $em->getRepository(Vehicule::class)->find($vehiculeId);

        if (! $conducteur || 'CONDUCTEUR' !== $conducteur->getRole()) {
            return $this->json(['message' => 'Conducteur introuvable'], Response::HTTP_NOT_FOUND);
        }
        if (! $vehicule) {
            return $this->json(['message' => 'Véhicule introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Clôturer l'ancienne affectation du conducteur si elle existe
        $ancienneAffectation = $em->getRepository(Affectation::class)
            ->createQueryBuilder('a')
            ->where('a.conducteur = :conducteur')
            ->andWhere('a.dateFin IS NULL OR a.dateFin > :now')
            ->setParameter('conducteur', $conducteur)
            ->setParameter('now', new \DateTime())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($ancienneAffectation) {
            $ancienneAffectation->setDateFin(new \DateTime());
            $ancienVehicule = $ancienneAffectation->getVehicule();
            $ancienVehicule->setStatut('disponible');
        }

        // Nouvelle affectation
        $affectation = new Affectation();
        $affectation->setConducteur($conducteur);
        $affectation->setVehicule($vehicule);
        $affectation->setDateDebut(new \DateTime());
        $affectation->setCommentaire($commentaire);

        $vehicule->setStatut('en_mission');

        $em->persist($affectation);
        $em->flush();

        return $this->json([
            'message' => sprintf('Véhicule %s affecté à %s %s', $vehicule->getImmatriculation(), $conducteur->getPrenom(), $conducteur->getNom()),
            'affectationId' => $affectation->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/desaffecter/{affectationId}', name: 'gestionnaire_desaffecter', methods: ['DELETE'])]
    public function desaffecterVehicule(int $affectationId, EntityManagerInterface $em): JsonResponse
    {
        $affectation = $em->getRepository(Affectation::class)->find($affectationId);

        if (! $affectation) {
            return $this->json(['message' => 'Affectation introuvable'], Response::HTTP_NOT_FOUND);
        }

        $affectation->setDateFin(new \DateTime());
        $affectation->getVehicule()->setStatut('disponible');

        $em->flush();

        return $this->json(['message' => 'Véhicule libéré avec succès']);
    }

    #[Route('/vue-flotte', name: 'gestionnaire_vue_flotte', methods: ['GET'])]
    public function vueFlotte(EntityManagerInterface $em): JsonResponse
    {
        $vehicules = $em->getRepository(Vehicule::class)
            ->createQueryBuilder('v')
            ->leftJoin('v.categorie', 'c')->addSelect('c')
            ->orderBy('v.immatriculation', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($vehicules as $v) {
            $affectationActive = $em->getRepository(Affectation::class)
                ->createQueryBuilder('a')
                ->leftJoin('a.conducteur', 'u')->addSelect('u')
                ->where('a.vehicule = :vehicule')
                ->andWhere('a.dateFin IS NULL OR a.dateFin > :now')
                ->setParameter('vehicule', $v)
                ->setParameter('now', new \DateTime())
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $result[] = [
                'id' => $v->getId(),
                'immatriculation' => $v->getImmatriculation(),
                'marque' => $v->getMarque(),
                'modele' => $v->getModele(),
                'annee' => $v->getAnnee(),
                'kilometrage' => $v->getKilometrage(),
                'quotaKmAnnuel' => $v->getQuotaKmAnnuel(),
                'pourcentageQuota' => $v->getPourcentageQuota(),
                'statut' => $v->getStatut(),
                'categorie' => $v->getCategorie()?->getLibelle(),
                'conducteur' => $affectationActive ? [
                    'id' => $affectationActive->getConducteur()->getId(),
                    'nom' => $affectationActive->getConducteur()->getNom(),
                    'prenom' => $affectationActive->getConducteur()->getPrenom(),
                    'email' => $affectationActive->getConducteur()->getEmail(),
                    'affectationId' => $affectationActive->getId(),
                    'dateDebut' => $affectationActive->getDateDebut()?->format('d/m/Y'),
                ] : null,
            ];
        }

        return $this->json($result);
    }

    #[Route('/changer-statut/{vehiculeId}', name: 'gestionnaire_changer_statut', methods: ['PATCH'])]
    public function changerStatut(int $vehiculeId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $vehicule = $em->getRepository(Vehicule::class)->find($vehiculeId);

        if (! $vehicule) {
            return $this->json(['message' => 'Véhicule introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $statut = $data['statut'] ?? '';

        $statutsValides = ['disponible', 'en_mission', 'maintenance', 'inactif'];
        if (! in_array($statut, $statutsValides)) {
            return $this->json(['message' => 'Statut invalide'], Response::HTTP_BAD_REQUEST);
        }

        $vehicule->setStatut($statut);
        $em->flush();

        return $this->json(['message' => 'Statut mis à jour', 'statut' => $statut]);
    }
}
