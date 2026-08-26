<?php

namespace App\EventListener;

use App\Service\AuditLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Journalise chaque refus d'accès (403). Un accès refusé à un utilisateur
 * authentifié est un signal de sécurité : il révèle une tentative d'agir hors
 * de son périmètre, exactement la classe de problème corrigée au chapitre IX.
 */
#[AsEventListener(event: 'kernel.exception')]
final class AccessDeniedAuditListener
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (! $exception instanceof AccessDeniedHttpException && ! $exception instanceof AccessDeniedException) {
            return;
        }

        $this->audit->enregistrer(
            'acces_refuse',
            $event->getRequest()->getMethod() . ' ' . $event->getRequest()->getPathInfo(),
        );
    }
}
