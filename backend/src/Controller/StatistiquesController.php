<?php

namespace App\Controller;

use App\Repository\DocumentRepository;
use App\Repository\EntretienRepository;
use App\Repository\PleinRepository;
use App\Repository\VehiculeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/statistiques')]
#[IsGranted('ROLE_GESTIONNAIRE')]
class StatistiquesController extends AbstractController
{
    public function __construct(
        private readonly VehiculeRepository $vehiculeRepository,
        private readonly EntretienRepository $entretienRepository,
        private readonly PleinRepository $pleinRepository,
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    #[Route('', name: 'statistiques_globales', methods: ['GET'])]
    public function globales(): JsonResponse
    {
        return $this->json([
            'coutsParMois' => $this->coutsParMois(),
            'consommationParVehicule' => $this->consommationParVehicule(),
            'documentsExpirant' => $this->documentsExpirant(),
            'totaux' => $this->totaux(),
        ]);
    }

    /**
     * Coûts entretien + carburant agrégés sur les 12 derniers mois.
     */
    private function coutsParMois(): array
    {
        $mois = [];
        for ($i = 11; $i >= 0; --$i) {
            $debut = new \DateTime("first day of -{$i} months 00:00:00");
            $fin = (clone $debut)->modify('last day of this month 23:59:59');
            $mois[] = [
                'mois' => $debut->format('Y-m'),
                'label' => $debut->format('M Y'),
                'entretien' => round($this->entretienRepository->findCoutMaintenanceByPeriod($debut, $fin), 2),
                'carburant' => round($this->pleinRepository->getCoutCarburantByPeriod($debut, $fin), 2),
            ];
        }

        return $mois;
    }

    private function consommationParVehicule(): array
    {
        $result = [];
        foreach ($this->vehiculeRepository->findAll() as $v) {
            $conso = $this->pleinRepository->getConsommationMoyenne($v);
            if (null === $conso) {
                continue;
            }
            $result[] = [
                'immatriculation' => $v->getImmatriculation(),
                'modele' => $v->getMarque() . ' ' . $v->getModele(),
                'consommation' => $conso,
            ];
        }

        usort($result, fn ($a, $b) => $b['consommation'] <=> $a['consommation']);

        return $result;
    }

    private function documentsExpirant(): array
    {
        $result = [];
        foreach ($this->documentRepository->findExpirant(60) as $doc) {
            $result[] = [
                'id' => $doc->getId(),
                'type' => $doc->getType(),
                'vehicule' => $doc->getVehicule()?->getImmatriculation(),
                'dateExpiration' => $doc->getDateExpiration()?->format('Y-m-d'),
                'jours' => $doc->getJoursAvantExpiration(),
                'expire' => $doc->isExpire(),
            ];
        }

        return $result;
    }

    private function totaux(): array
    {
        $debut = new \DateTime('-12 months');
        $fin = new \DateTime();

        return [
            'carburant12Mois' => round($this->pleinRepository->getCoutCarburantByPeriod($debut, $fin), 2),
            'entretien12Mois' => round($this->entretienRepository->findCoutMaintenanceByPeriod($debut, $fin), 2),
            'documentsExpirant' => count($this->documentRepository->findExpirant(60)),
        ];
    }
}
