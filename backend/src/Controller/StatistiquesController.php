<?php

namespace App\Controller;

use App\Repository\DocumentRepository;
use App\Repository\EntretienRepository;
use App\Repository\PleinRepository;
use App\Repository\VehiculeRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/export-pdf', name: 'statistiques_export_pdf', methods: ['GET'])]
    public function exportPdf(): Response
    {
        $html = $this->genererHtmlRapport($this->totaux(), $this->coutsParMois(), $this->consommationParVehicule());

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rapport-flotte.pdf"',
        ]);
    }

    /**
     * @param array<string, mixed>             $totaux
     * @param array<int, array<string, mixed>> $couts
     * @param array<int, array<string, mixed>> $conso
     */
    private function genererHtmlRapport(array $totaux, array $couts, array $conso): string
    {
        $esc = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES);
        $euros = static fn (mixed $v): string => number_format((float) $v, 2, ',', ' ') . ' €';

        $lignesCouts = '';
        foreach ($couts as $mois) {
            $lignesCouts .= '<tr><td>' . $esc($mois['label'] ?? '') . '</td>'
                . '<td class="r">' . $euros($mois['entretien'] ?? 0) . '</td>'
                . '<td class="r">' . $euros($mois['carburant'] ?? 0) . '</td></tr>';
        }

        $lignesConso = '';
        foreach ($conso as $v) {
            $lignesConso .= '<tr><td>' . $esc($v['immatriculation'] ?? '') . '</td>'
                . '<td>' . $esc($v['modele'] ?? '') . '</td>'
                . '<td class="r">' . $esc($v['consommation'] ?? '—') . ' L/100 km</td></tr>';
        }

        return '<html><head><style>
            body { font-family: Helvetica, sans-serif; color: #1c1c28; font-size: 12px; }
            h1 { color: #4F46E5; font-size: 22px; margin-bottom: 2px; }
            .sub { color: #777; font-size: 11px; margin-bottom: 18px; }
            h2 { color: #33308f; font-size: 14px; margin-top: 22px; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
            table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            th { background: #4F46E5; color: #fff; text-align: left; padding: 6px 8px; font-size: 11px; }
            td { padding: 5px 8px; border-bottom: 1px solid #eee; }
            .r { text-align: right; }
            .kpi { display: inline-block; border: 1px solid #ddd; padding: 8px 14px; margin-right: 10px; }
            .kpi b { display: block; font-size: 18px; color: #4F46E5; }
            .kpi span { font-size: 10px; color: #777; text-transform: uppercase; }
            </style></head><body>
            <h1>OptiFleet — Rapport de flotte</h1>
            <div class="sub">Généré le ' . date('d/m/Y') . '</div>
            <div>
              <div class="kpi"><b>' . $euros($totaux['carburant12Mois'] ?? 0) . '</b><span>Carburant 12 mois</span></div>
              <div class="kpi"><b>' . $euros($totaux['entretien12Mois'] ?? 0) . '</b><span>Entretien 12 mois</span></div>
              <div class="kpi"><b>' . $esc($totaux['documentsExpirant'] ?? 0) . '</b><span>Documents à échéance</span></div>
            </div>
            <h2>Coûts mensuels consolidés</h2>
            <table><tr><th>Mois</th><th class="r">Entretien</th><th class="r">Carburant</th></tr>' . $lignesCouts . '</table>
            <h2>Consommation moyenne par véhicule</h2>
            <table><tr><th>Immatriculation</th><th>Modèle</th><th class="r">Consommation</th></tr>' . $lignesConso . '</table>
            </body></html>';
    }

    /**
     * Coûts entretien + carburant agrégés sur les 12 derniers mois.
     */
    /**
     * @return array<int, array<string, mixed>>
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

    /**
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * @return array<string, mixed>
     */
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
