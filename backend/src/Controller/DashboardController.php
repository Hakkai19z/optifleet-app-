<?php

namespace App\Controller;

use App\Repository\AlerteRepository;
use App\Repository\VehiculeRepository;
use App\Service\EntretienService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/dashboard')]
#[IsGranted('ROLE_CONDUCTEUR')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly VehiculeRepository $vehiculeRepository,
        private readonly AlerteRepository $alerteRepository,
        private readonly EntretienService $entretienService,
    ) {}

    #[Route('/vehicules-quota', name: 'dashboard_vehicules_quota', methods: ['GET'])]
    public function vehiculesQuota(): JsonResponse
    {
        $vehicules = $this->vehiculeRepository->findAll();

        $result = [];
        foreach ($vehicules as $v) {
            if ($v->getQuotaKmAnnuel() === null) continue;
            $result[] = [
                'id' => $v->getId(),
                'immatriculation' => $v->getImmatriculation(),
                'marque' => $v->getMarque(),
                'modele' => $v->getModele(),
                'kilometrage' => $v->getKilometrage(),
                'quotaKmAnnuel' => $v->getQuotaKmAnnuel(),
                'pourcentage' => $v->getPourcentageQuota(),
                'depasse' => $v->isQuotaDepasse(),
                'statut' => $v->getStatut(),
            ];
        }

        usort($result, fn($a, $b) => $b['pourcentage'] <=> $a['pourcentage']);

        return $this->json($result);
    }

    #[Route('/stats', name: 'dashboard_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $vehiculesByStatut = [];
        foreach ($this->vehiculeRepository->countByStatut() as $row) {
            $vehiculesByStatut[$row['statut']] = (int) $row['total'];
        }

        $debut = new \DateTime('-12 months');
        $fin = new \DateTime();
        $coutMaintenance12Mois = $this->entretienService->getCoutTotalByPeriod($debut, $fin);

        $totalVehicules = array_sum($vehiculesByStatut);
        $vehiculesDisponibles = $vehiculesByStatut['disponible'] ?? 0;
        $tauxDisponibilite = $totalVehicules > 0 ? round(($vehiculesDisponibles / $totalVehicules) * 100, 2) : 0;

        return $this->json([
            'vehicules' => [
                'total' => $totalVehicules,
                'parStatut' => $vehiculesByStatut,
                'tauxDisponibilite' => $tauxDisponibilite,
            ],
            'alertes' => [
                'actives' => count($this->alerteRepository->findActiveAlertes()),
            ],
            'maintenance' => [
                'cout12Mois' => $coutMaintenance12Mois,
            ],
        ]);
    }
}
