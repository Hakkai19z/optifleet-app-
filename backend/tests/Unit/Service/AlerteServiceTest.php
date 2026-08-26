<?php

namespace App\Tests\Unit\Service;

use App\Entity\Alerte;
use App\Entity\Vehicule;
use App\Repository\AlerteRepository;
use App\Repository\DocumentRepository;
use App\Repository\EntretienRepository;
use App\Repository\UtilisateurRepository;
use App\Service\AlerteService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AlerteServiceTest extends TestCase
{
    private AlerteService $alerteService;
    private EntityManagerInterface&MockObject $entityManager;
    private AlerteRepository&MockObject $alerteRepository;
    private EntretienRepository&MockObject $entretienRepository;
    private DocumentRepository&MockObject $documentRepository;
    private UtilisateurRepository&MockObject $utilisateurRepository;
    private NotificationService&MockObject $notification;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->alerteRepository = $this->createMock(AlerteRepository::class);
        $this->entretienRepository = $this->createMock(EntretienRepository::class);
        $this->documentRepository = $this->createMock(DocumentRepository::class);
        $this->utilisateurRepository = $this->createMock(UtilisateurRepository::class);
        $this->notification = $this->createMock(NotificationService::class);

        $this->alerteService = new AlerteService(
            $this->entityManager,
            $this->alerteRepository,
            $this->entretienRepository,
            $this->documentRepository,
            $this->utilisateurRepository,
            $this->notification
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
        $this->documentRepository->expects($this->once())
            ->method('findExpirant')
            ->willReturn([]);
        $this->alerteRepository->expects($this->once())
            ->method('findActiveAlertes')
            ->willReturn([]);

        $count = $this->alerteService->verifierEcheances();
        $this->assertSame(0, $count);
    }

    // Une alerte d'échéance dont la condition a disparu est auto-résolue ;
    // un signalement manuel (type 'autre') n'est jamais touché.
    public function testAutoResolutionAlerteObsolete(): void
    {
        $this->entretienRepository->method('findEchus')->willReturn([]);
        $this->documentRepository->method('findExpirant')->willReturn([]);

        $vehicule = new Vehicule();

        $alerteEcheance = new Alerte();
        $alerteEcheance->setVehicule($vehicule);
        $alerteEcheance->setType('vidange');
        $alerteEcheance->setStatut('en_attente');

        $alerteManuelle = new Alerte();
        $alerteManuelle->setVehicule($vehicule);
        $alerteManuelle->setType('autre');
        $alerteManuelle->setStatut('en_attente');

        $this->alerteRepository->method('findActiveAlertes')
            ->willReturn([$alerteEcheance, $alerteManuelle]);

        $this->alerteService->verifierEcheances();

        $this->assertSame('resolue', $alerteEcheance->getStatut(), 'Alerte d\'échéance obsolète auto-résolue');
        $this->assertSame('en_attente', $alerteManuelle->getStatut(), 'Signalement manuel préservé');
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
