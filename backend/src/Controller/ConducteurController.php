<?php

namespace App\Controller;

use App\Entity\Affectation;
use App\Entity\Reservation;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/conducteur')]
#[IsGranted('ROLE_USER')]
class ConducteurController extends AbstractController
{
    #[Route('/mon-vehicule', name: 'conducteur_mon_vehicule', methods: ['GET'])]
    public function monVehicule(EntityManagerInterface $em): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // Réservations en cours ou à venir du conducteur (hors annulées/passées)
        $reservations = $em->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.vehicule', 'rv')->addSelect('rv')
            ->where('r.conducteur = :user')
            ->andWhere('r.statut != :annulee')
            ->andWhere('r.dateFin >= :now')
            ->setParameter('user', $user)
            ->setParameter('annulee', 'annulee')
            ->setParameter('now', new \DateTime())
            ->orderBy('r.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

        $reservationsJson = array_map(fn (Reservation $r) => [
            'id' => $r->getId(),
            'dateDebut' => $r->getDateDebut()?->format('d/m/Y H:i'),
            'dateFin' => $r->getDateFin()?->format('d/m/Y H:i'),
            'statut' => $r->getStatut(),
            'motif' => $r->getMotif(),
            'vehicule' => $r->getVehicule() ? [
                'id' => $r->getVehicule()->getId(),
                'immatriculation' => $r->getVehicule()->getImmatriculation(),
                'marque' => $r->getVehicule()->getMarque(),
                'modele' => $r->getVehicule()->getModele(),
                'statut' => $r->getVehicule()->getStatut(),
            ] : null,
        ], $reservations);

        $affectation = $em->getRepository(Affectation::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.vehicule', 'v')->addSelect('v')
            ->leftJoin('v.categorie', 'c')->addSelect('c')
            ->where('a.conducteur = :user')
            ->andWhere('a.dateFin IS NULL OR a.dateFin > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.dateDebut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$affectation) {
            return $this->json(['vehicule' => null, 'affectation' => null, 'entretiens' => [], 'reservations' => $reservationsJson]);
        }

        $vehicule = $affectation->getVehicule();
        $entretiens = $vehicule->getEntretiens()->toArray();
        usort($entretiens, fn($a, $b) => $b->getDateRealise() <=> $a->getDateRealise());

        return $this->json([
            'affectation' => [
                'id' => $affectation->getId(),
                'dateDebut' => $affectation->getDateDebut()?->format('d/m/Y H:i'),
                'commentaire' => $affectation->getCommentaire(),
            ],
            'vehicule' => [
                'id' => $vehicule->getId(),
                'immatriculation' => $vehicule->getImmatriculation(),
                'marque' => $vehicule->getMarque(),
                'modele' => $vehicule->getModele(),
                'annee' => $vehicule->getAnnee(),
                'kilometrage' => $vehicule->getKilometrage(),
                'quotaKmAnnuel' => $vehicule->getQuotaKmAnnuel(),
                'statut' => $vehicule->getStatut(),
                'adresse' => $vehicule->getAdresse(),
                'latitude' => $vehicule->getLatitude(),
                'longitude' => $vehicule->getLongitude(),
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
            'reservations' => $reservationsJson,
        ]);
    }

    #[Route('/signaler-probleme', name: 'conducteur_signaler_probleme', methods: ['POST'])]
    public function signalerProbleme(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message)) {
            return $this->json(['message' => 'Le message est requis'], Response::HTTP_BAD_REQUEST);
        }

        $affectation = $em->getRepository(Affectation::class)
            ->createQueryBuilder('a')
            ->leftJoin('a.vehicule', 'v')->addSelect('v')
            ->where('a.conducteur = :user')
            ->andWhere('a.dateFin IS NULL OR a.dateFin > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.dateDebut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$affectation) {
            return $this->json(['message' => 'Aucun véhicule affecté'], Response::HTTP_NOT_FOUND);
        }

        $vehicule = $affectation->getVehicule();

        $alerte = new \App\Entity\Alerte();
        $alerte->setVehicule($vehicule);
        $alerte->setType('autre');
        $alerte->setMessage(sprintf(
            '[Signalement conducteur %s %s] %s',
            $user->getPrenom(),
            $user->getNom(),
            $message
        ));
        $alerte->setDateEcheance(new \DateTime());
        $alerte->setStatut('en_attente');

        $em->persist($alerte);
        $em->flush();

        return $this->json(['message' => 'Problème signalé avec succès', 'id' => $alerte->getId()]);
    }
}
