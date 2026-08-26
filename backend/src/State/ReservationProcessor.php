<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Reservation;
use App\Entity\Utilisateur;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * À la création d'une réservation, force le conducteur à l'utilisateur
 * authentifié lorsqu'il n'est pas gestionnaire. Empêche un conducteur de créer
 * une réservation au nom d'un autre utilisateur (usurpation / blocage de flotte).
 *
 * @implements ProcessorInterface<Reservation, Reservation>
 */
final class ReservationProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Reservation, Reservation> $persistProcessor
     */
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Reservation && ! $this->security->isGranted('ROLE_GESTIONNAIRE')) {
            $user = $this->security->getUser();
            if ($user instanceof Utilisateur) {
                $data->setConducteur($user);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
