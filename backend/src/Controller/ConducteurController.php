<?php

namespace App\Controller;

use App\Entity\Affectation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/conducteur')]
#[IsGranted('ROLE_USER')]
class ConducteurController extends AbstractController
{
    #[Route('/mon-vehicule', name: 'conducteur_mon_vehicule', methods: ['GET'])]
    public function monVehicule(EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

        $affectation = $em->getRepository(Affectation::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.vehicule', 'v')
            ->addSelect('v')
            ->leftJoin('v.categorie', 'c')
            ->addSelect('c')
            ->where('a.conducteur = :user')
            ->andWhere('a.dateFin IS NULL OR a.dateFin > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.dateDebut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$affectation) {
            return $this->json(['vehicule' => null, 'affectation' => null, 'entretiens' => []]);
        }

        $vehicule = $affectation->getVehicule();
        $entretiens = $vehicule->getEntretiens()->toArray();
        usort($entretiens, fn($a, $b) => $b->getDateRealise() <=> $a->getDateRealise());

        return $this->json([
            'affectation' => [
                'id' => $affectation->getId(),
                'dateDebut' => $affectation->getDateDebut()?->format('Y-m-d H:i'),
                'commentaire' => $affectation->getCommentaire(),
            ],
            'vehicule' => [
                'id' => $vehicule->getId(),
                'immatriculation' => $vehicule->getImmatriculation(),
                'marque' => $vehicule->getMarque(),
                'modele' => $vehicule->getModele(),
                'annee' => $vehicule->getAnnee(),
                'kilometrage' => $vehicule->getKilometrage(),
                'statut' => $vehicule->getStatut(),
                'adresse' => $vehicule->getAdresse(),
                'categorie' => $vehicule->getCategorie()?->getLibelle(),
            ],
            'entretiens' => array_map(fn($e) => [
                'id' => $e->getId(),
                'type' => $e->getType(),
                'dateRealise' => $e->getDateRealise()?->format('Y-m-d'),
                'dateProchaine' => $e->getDateProchaine()?->format('Y-m-d'),
                'cout' => $e->getCout(),
                'notes' => $e->getNotes(),
            ], array_slice($entretiens, 0, 5)),
        ]);
    }
}
