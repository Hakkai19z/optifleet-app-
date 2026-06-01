<?php

namespace App\Tests\Unit\Service;

use App\Entity\Alerte;
use App\Entity\Vehicule;
use App\Repository\AlerteRepository;
use App\Repository\EntretienRepository;
use App\Service\AlerteService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AlerteServiceTest extends TestCase
{
    private AlerteService $alerteService;
    private EntityManagerInterface&MockObject $entityManager;
    private AlerteRepository&MockObject $alerteRepository;
    private EntretienRepository&MockObject $entretienRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->alerteRepository = $this->createMock(AlerteRepository::class);
        $this->entretienRepository = $this->createMock(EntretienRepository::class);

        $this->alerteService = new AlerteService(
            $this->entityManager,
            $this->alerteRepository,
            $this->entretienRepository
        );
    }

    public function testCreerAlerte(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setImmatriculation('AB-123-CD');
        $vehicule->setMarque('Renault');
        $vehicule->setModele('Clio');
        $vehicule->setAnnee(2022);

        $dateEcheance = new \DateTime('+30 days');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $alerte = $this->alerteService->creerAlerte($vehicule, 'revision', 'Révision à planifier', $dateEcheance);

        $this->assertInstanceOf(Alerte::class, $alerte);
        $this->assertSame($vehicule, $alerte->getVehicule());
        $this->assertSame('revision', $alerte->getType());
        $this->assertSame('en_attente', $alerte->getStatut());
    }

    public function testVerifierEcheancesAucunEntretien(): void
    {
        $this->entretienRepository->expects($this->once())
            ->method('findEchus')
            ->willReturn([]);

        $count = $this->alerteService->verifierEcheances();
        $this->assertSame(0, $count);
    }

    public function testCountActiveAlertes(): void
    {
        $this->alerteRepository->expects($this->once())
            ->method('findActiveAlertes')
            ->willReturn([new Alerte(), new Alerte()]);

        $count = $this->alerteService->countActiveAlertes();
        $this->assertSame(2, $count);
    }
}
