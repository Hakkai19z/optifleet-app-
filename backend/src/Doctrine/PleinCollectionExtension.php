<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Affectation;
use App\Entity\Plein;
use App\Entity\Utilisateur;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Restreint la collection des pleins : un conducteur ne voit que les pleins des
 * véhicules qu'il conduit ou a conduits (affectation active ou passée). Les
 * gestionnaires et administrateurs voient toute la flotte.
 */
final class PleinCollectionExtension implements QueryCollectionExtensionInterface
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
        if (Plein::class !== $resourceClass) {
            return;
        }

        if ($this->security->isGranted('ROLE_GESTIONNAIRE')) {
            return;
        }

        $user = $this->security->getUser();
        if (! $user instanceof Utilisateur) {
            // Aucun utilisateur exploitable : ne rien renvoyer.
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $subAlias = $queryNameGenerator->generateJoinAlias('aff');

        // Le plein est visible si une affectation lie le conducteur courant au
        // véhicule du plein.
        $queryBuilder
            ->andWhere(sprintf(
                'EXISTS (SELECT %1$s FROM %2$s %1$s WHERE %1$s.vehicule = %3$s.vehicule AND %1$s.conducteur = :current_user)',
                $subAlias,
                Affectation::class,
                $rootAlias
            ))
            ->setParameter('current_user', $user);
    }
}
