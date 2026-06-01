<?php

namespace App\Service;

use App\Entity\Alerte;
use App\Entity\Entretien;
use App\Entity\Vehicule;
use App\Repository\AlerteRepository;
use App\Repository\EntretienRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlerteService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AlerteRepository $alerteRepository,
        private readonly EntretienRepository $entretienRepository,
    ) {}

    public function creerAlerte(Vehicule $vehicule, string $type, string $message, \DateTimeInterface $dateEcheance): Alerte
    {
        $alerte = new Alerte();
        $alerte->setVehicule($vehicule);
        $alerte->setType($type);
        $alerte->setMessage($message);
        $alerte->setDateEcheance($dateEcheance);
        $alerte->setStatut('en_attente');

        $this->entityManager->persist($alerte);
        $this->entityManager->flush();

        return $alerte;
    }

    public function verifierEcheances(): int
    {
        $count = 0;
        $entretienEchus = $this->entretienRepository->findEchus();

        foreach ($entretienEchus as $entretien) {
            if (!$this->alerteRepository->existsForVehiculeAndType($entretien->getVehicule(), $entretien->getType())) {
                $this->creerAlerteEntretien($entretien);
                $count++;
            }
        }

        return $count;
    }

    private function creerAlerteEntretien(Entretien $entretien): void
    {
        $vehicule = $entretien->getVehicule();
        $message = sprintf(
            'Entretien %s échu pour le véhicule %s %s (%s)',
            $entretien->getType(),
            $vehicule->getMarque(),
            $vehicule->getModele(),
            $vehicule->getImmatriculation()
        );

        $dateEcheance = $entretien->getDateProchaine() ?? new \DateTime();

        $alerte = new Alerte();
        $alerte->setVehicule($vehicule);
        $alerte->setType($entretien->getType() === 'CT' ? 'CT' : ($entretien->getType() === 'vidange' ? 'vidange' : 'revision'));
        $alerte->setMessage($message);
        $alerte->setDateEcheance($dateEcheance);
        $alerte->setStatut('en_attente');

        $this->entityManager->persist($alerte);
    }

    public function countActiveAlertes(): int
    {
        return count($this->alerteRepository->findActiveAlertes());
    }
}
