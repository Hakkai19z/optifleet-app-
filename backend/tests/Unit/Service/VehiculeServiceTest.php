<?php

namespace App\Tests\Unit\Service;

use App\Entity\Vehicule;
use App\Repository\VehiculeRepository;
use App\Service\VehiculeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VehiculeServiceTest extends TestCase
{
    private VehiculeService $vehiculeService;
    private EntityManagerInterface&MockObject $entityManager;
    private VehiculeRepository&MockObject $vehiculeRepository;
    private HttpClientInterface&MockObject $httpClient;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->vehiculeRepository = $this->createMock(VehiculeRepository::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->vehiculeService = new VehiculeService(
            $this->entityManager,
            $this->vehiculeRepository,
            $this->httpClient,
            ''
        );
    }

    public function testCreerVehicule(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setImmatriculation('AB-123-CD');
        $vehicule->setMarque('Renault');
        $vehicule->setModele('Clio');
        $vehicule->setAnnee(2022);

        $this->entityManager->expects($this->once())->method('persist')->with($vehicule);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->vehiculeService->creerVehicule($vehicule);

        $this->assertSame($vehicule, $result);
    }

    public function testImmatriculationInvalide(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setImmatriculation('INVALID');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Immatriculation invalide');

        $this->vehiculeService->creerVehicule($vehicule);
    }

    public function testImmatriculationValide(): void
    {
        $validFormats = ['AB-123-CD', 'ZZ-999-ZZ', 'AA-000-AA'];

        foreach ($validFormats as $format) {
            $this->assertTrue(
                $this->vehiculeService->validerImmatriculation($format),
                "Format $format devrait être valide"
            );
        }
    }

    public function testImmatriculationInvalideFormats(): void
    {
        $invalidFormats = ['AB123CD', 'ab-123-cd', 'AB-1234-CD', 'A-123-CD'];

        foreach ($invalidFormats as $format) {
            $this->assertFalse(
                $this->vehiculeService->validerImmatriculation($format),
                "Format $format devrait être invalide"
            );
        }
    }

    public function testIsDisponible(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setStatut('disponible');
        $this->assertTrue($this->vehiculeService->isDisponible($vehicule));

        $vehicule->setStatut('en_mission');
        $this->assertFalse($this->vehiculeService->isDisponible($vehicule));

        $vehicule->setStatut('maintenance');
        $this->assertFalse($this->vehiculeService->isDisponible($vehicule));
    }
}
