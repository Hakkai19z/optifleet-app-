<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Entity\Vehicule;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VehiculeVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Vehicule;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (! $user instanceof Utilisateur) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT => in_array('ROLE_GESTIONNAIRE', $user->getRoles()),
            self::DELETE => in_array('ROLE_ADMIN', $user->getRoles()),
            default => false,
        };
    }
}
