<?php

namespace App\Service;

use App\Entity\Entretien;
use App\Entity\Vehicule;
use App\Repository\EntretienRepository;
use Doctrine\ORM\EntityManagerInterface;

class EntretienService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntretienRepository $entretienRepository,
    ) {}

    public function planifier(Vehicule $vehicule, string $type, \DateTimeInterface $dateRealise, ?\DateTimeInterface $dateProchaine = null, ?int $kmProchaine = null, ?float $cout = null): Entretien
    {
        $entretien = new Entretien();
        $entretien->setVehicule($vehicule);
        $entretien->setType($type);
        $entretien->setDateRealise($dateRealise);
        $entretien->setDateProchaine($dateProchaine);
        $entretien->setKmProchaine($kmProchaine);

        if ($cout !== null) {
            $entretien->setCout((string) $cout);
        }

        $this->entityManager->persist($entretien);
        $this->entityManager->flush();

        return $entretien;
    }

    public function isEchu(Entretien $entretien): bool
    {
        return $entretien->isEchu();
    }

    public function getCoutTotalByPeriod(\DateTimeInterface $debut, \DateTimeInterface $fin): float
    {
        return $this->entretienRepository->findCoutMaintenanceByPeriod($debut, $fin);
    }
}
