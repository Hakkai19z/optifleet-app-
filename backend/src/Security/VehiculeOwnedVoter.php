<?php

namespace App\Security;

use App\Entity\Alerte;
use App\Entity\Document;
use App\Entity\Entretien;
use App\Entity\Utilisateur;
use App\Repository\AffectationRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Lecture unitaire d'une donnée rattachée à un véhicule (entretien, alerte,
 * document) : autorisée pour un gestionnaire, ou pour un conducteur ayant (ou
 * ayant eu) une affectation sur le véhicule concerné.
 */
class VehiculeOwnedVoter extends Voter
{
    public const VOIR = 'VOIR';

    public function __construct(private readonly AffectationRepository $affectationRepository)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VOIR === $attribute
            && ($subject instanceof Entretien || $subject instanceof Alerte || $subject instanceof Document);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (! $user instanceof Utilisateur) {
            return false;
        }

        if (in_array('ROLE_GESTIONNAIRE', $user->getRoles(), true)) {
            return true;
        }

        $vehicule = $subject->getVehicule();
        if (null === $vehicule) {
            return false;
        }

        return $this->affectationRepository->existsForConducteurAndVehicule($user, $vehicule);
    }
}
