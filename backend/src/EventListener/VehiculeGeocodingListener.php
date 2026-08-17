<?php

namespace App\EventListener;

use App\Entity\Vehicule;
use App\Service\VehiculeService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, entity: Vehicule::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Vehicule::class)]
class VehiculeGeocodingListener
{
    public function __construct(
        private readonly VehiculeService $vehiculeService,
    ) {
    }

    public function prePersist(Vehicule $vehicule): void
    {
        $this->geocodeIfNeeded($vehicule);
    }

    public function preUpdate(Vehicule $vehicule): void
    {
        $this->geocodeIfNeeded($vehicule);
    }

    private function geocodeIfNeeded(Vehicule $vehicule): void
    {
        if (null !== $vehicule->getAdresse() && null === $vehicule->getLatitude()) {
            $this->vehiculeService->geocodeAdresse($vehicule);
        }
    }
}
