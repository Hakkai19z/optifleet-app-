<?php

namespace App\Scheduler;

use App\Service\AlerteService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class VerifierAlertesHandler
{
    public function __construct(
        private readonly AlerteService $alerteService,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(VerifierAlertesMessage $message): void
    {
        $count = $this->alerteService->verifierEcheances();
        $this->logger->info(sprintf('Vérification alertes: %d nouvelles alertes créées', $count));
    }
}
