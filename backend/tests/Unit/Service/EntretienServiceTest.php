<?php

namespace App\Tests\Unit\Service;

use App\Entity\Entretien;
use App\Entity\Vehicule;
use App\Repository\EntretienRepository;
use App\Service\EntretienService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntretienServiceTest extends TestCase
{
    private EntretienService $entretienService;
    private EntityManagerInterface&MockObject $entityManager;
    private EntretienRepository&MockObject $entretienRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entretienRepository = $this->createMock(EntretienRepository::class);

        $this->entretienService = new EntretienService(
            $this->entityManager,
            $this->entretienRepository
        );
    }

    public function testPlanifier(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setImmatriculation('AB-123-CD');
        $vehicule->setMarque('Renault');
        $vehicule->setModele('Clio');
        $vehicule->setAnnee(2022);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $entretien = $this->entretienService->planifier(
            $vehicule,
            'vidange',
            new \DateTime('-1 week'),
            new \DateTime('+6 months'),
            60000,
            120.50
        );

        $this->assertInstanceOf(Entretien::class, $entretien);
        $this->assertSame('vidange', $entretien->getType());
        $this->assertSame($vehicule, $entretien->getVehicule());
    }

    public function testIsEchuParDate(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setImmatriculation('AB-123-CD');
        $vehicule->setMarque('Renault');
        $vehicule->setModele('Clio');
        $vehicule->setAnnee(2022);
        $vehicule->setKilometrage(45000);

        $entretien = new Entretien();
        $entretien->setVehicule($vehicule);
        $entretien->setType('vidange');
        $entretien->setDateRealise(new \DateTime('-1 year'));
        $entretien->setDateProchaine(new \DateTime('-1 week'));

        $this->assertTrue($this->entretienService->isEchu($entretien));
    }

    public function testIsEchuParKilometrage(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setImmatriculation('AB-123-CD');
        $vehicule->setMarque('Renault');
        $vehicule->setModele('Clio');
        $vehicule->setAnnee(2022);
        $vehicule->setKilometrage(55000);

        $entretien = new Entretien();
        $entretien->setVehicule($vehicule);
        $entretien->setType('revision');
        $entretien->setDateRealise(new \DateTime('-1 year'));
        $entretien->setDateProchaine(new \DateTime('+6 months'));
        $entretien->setKmProchaine(50000);

        $this->assertTrue($this->entretienService->isEchu($entretien));
    }

    public function testIsNonEchu(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setImmatriculation('AB-123-CD');
        $vehicule->setMarque('Renault');
        $vehicule->setModele('Clio');
        $vehicule->setAnnee(2022);
        $vehicule->setKilometrage(45000);

        $entretien = new Entretien();
        $entretien->setVehicule($vehicule);
        $entretien->setType('vidange');
        $entretien->setDateRealise(new \DateTime('-1 week'));
        $entretien->setDateProchaine(new \DateTime('+6 months'));
        $entretien->setKmProchaine(60000);

        $this->assertFalse($this->entretienService->isEchu($entretien));
    }
}
