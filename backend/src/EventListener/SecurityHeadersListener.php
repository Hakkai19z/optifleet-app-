<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Ajoute les en-têtes de sécurité HTTP recommandés (OWASP Secure Headers) sur
 * toutes les réponses, sans dépendance tierce. En production, ajoute également
 * HSTS. Couvre : clickjacking (X-Frame-Options), sniffing MIME
 * (X-Content-Type-Options), fuite de referrer, isolation cross-origin et une
 * CSP restrictive adaptée à une API JSON.
 */
#[AsEventListener(event: 'kernel.response')]
final class SecurityHeadersListener
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;

        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'no-referrer');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        // API JSON : aucune ressource active n'est servie par le backend.
        $headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");

        if ('prod' === $this->kernel->getEnvironment()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
