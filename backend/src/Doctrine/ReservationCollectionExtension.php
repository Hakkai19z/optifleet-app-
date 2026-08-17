<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Reservation;
use App\Entity\Utilisateur;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Restreint la collection des réservations : un conducteur ne voit que les
 * siennes. Les gestionnaires et administrateurs voient toute la flotte.
 */
final class ReservationCollectionExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (Reservation::class !== $resourceClass) {
            return;
        }

        // Les gestionnaires (et au-dessus) voient toutes les réservations.
        if ($this->security->isGranted('ROLE_GESTIONNAIRE')) {
            return;
        }

        $user = $this->security->getUser();
        if (! $user instanceof Utilisateur) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.conducteur = :current_user', $rootAlias))
            ->setParameter('current_user', $user);
    }
}
