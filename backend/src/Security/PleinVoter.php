<?php

namespace App\Security;

use App\Entity\Plein;
use App\Entity\Utilisateur;
use App\Repository\AffectationRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PleinVoter extends Voter
{
    public const CREATE = 'PLEIN_CREATE';

    public function __construct(private readonly AffectationRepository $affectationRepository)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::CREATE === $attribute && $subject instanceof Plein;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var Plein $plein */
        $plein = $subject;
        $user = $token->getUser();

        if (! $user instanceof Utilisateur) {
            return false;
        }

        if (in_array('ROLE_GESTIONNAIRE', $user->getRoles(), true)) {
            return true;
        }

        $vehicule = $plein->getVehicule();
        if (null === $vehicule) {
            return false;
        }

        return $this->affectationRepository->isActiveForConducteurAndVehicule($user, $vehicule);
    }
}
