<?php

namespace App\Service;

use App\Entity\Vehicule;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VehiculeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VehiculeRepository $vehiculeRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly string $googleMapsApiKey,
    ) {
    }

    public function creerVehicule(Vehicule $vehicule): Vehicule
    {
        if (! $this->validerImmatriculation($vehicule->getImmatriculation() ?? '')) {
            throw new \InvalidArgumentException('Immatriculation invalide. Format requis: AA-000-AA');
        }

        if (null !== $vehicule->getAdresse()) {
            $this->geocodeAdresse($vehicule);
        }

        $this->entityManager->persist($vehicule);
        $this->entityManager->flush();

        return $vehicule;
    }

    public function validerImmatriculation(string $immatriculation): bool
    {
        return (bool) preg_match('/^[A-Z]{2}-[0-9]{3}-[A-Z]{2}$/', $immatriculation);
    }

    public function isDisponible(Vehicule $vehicule): bool
    {
        return 'disponible' === $vehicule->getStatut();
    }

    public function geocodeAdresse(Vehicule $vehicule): void
    {
        if (empty($this->googleMapsApiKey) || null === $vehicule->getAdresse()) {
            return;
        }

        try {
            $response = $this->httpClient->request('GET', 'https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'address' => $vehicule->getAdresse(),
                    'key' => $this->googleMapsApiKey,
                ],
            ]);

            $data = $response->toArray();

            if ('OK' === $data['status'] && ! empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                $vehicule->setLatitude((string) $location['lat']);
                $vehicule->setLongitude((string) $location['lng']);
            }
        } catch (\Exception) {
        }
    }

    public function getStatsByStatut(): array
    {
        return $this->vehiculeRepository->countByStatut();
    }
}
