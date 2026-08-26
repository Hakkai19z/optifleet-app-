<?php

namespace App\Service;

use App\Entity\JournalAudit;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Écrit une entrée dans le journal d'audit. L'écriture est tolérante aux
 * pannes : elle ne doit jamais faire échouer l'action métier qu'elle trace.
 */
class AuditLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public function enregistrer(string $action, ?string $cible = null, ?string $details = null, ?string $auteurEmail = null): void
    {
        try {
            $entree = new JournalAudit($action);
            $entree->setCible($cible);
            $entree->setDetails($details);

            $utilisateur = $this->security->getUser();
            if ($utilisateur instanceof Utilisateur) {
                $entree->setAuteurEmail($auteurEmail ?? $utilisateur->getUserIdentifier());
                $entree->setAuteurRole($utilisateur->getRole());
            } elseif (null !== $auteurEmail) {
                $entree->setAuteurEmail($auteurEmail);
            }

            $requete = $this->requestStack->getCurrentRequest();
            if (null !== $requete) {
                $entree->setAdresseIp($requete->getClientIp());
            }

            $this->entityManager->persist($entree);
            $this->entityManager->flush();
        } catch (\Throwable) {
            // La journalisation ne doit jamais interrompre l'opération tracée.
        }
    }
}
