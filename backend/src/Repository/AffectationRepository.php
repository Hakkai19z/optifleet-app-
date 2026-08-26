<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Utilisateur;
use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AffectationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Affectation::class);
    }

    public function isActiveForConducteurAndVehicule(Utilisateur $conducteur, Vehicule $vehicule): bool
    {
        $result = $this->createQueryBuilder('a')
            ->where('a.conducteur = :conducteur')
            ->andWhere('a.vehicule = :vehicule')
            ->andWhere('a.dateFin IS NULL OR a.dateFin > :now')
            ->setParameter('conducteur', $conducteur)
            ->setParameter('vehicule', $vehicule)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();

        return null !== $result;
    }

    /**
     * Indique si le conducteur a (ou a eu) au moins une affectation sur ce
     * véhicule — active ou passée. Sert au contrôle d'accès en lecture des
     * pleins : un conducteur ne voit que les pleins des véhicules qu'il conduit
     * ou a conduits.
     */
    public function existsForConducteurAndVehicule(Utilisateur $conducteur, Vehicule $vehicule): bool
    {
        $result = $this->createQueryBuilder('a')
            ->select('1')
            ->where('a.conducteur = :conducteur')
            ->andWhere('a.vehicule = :vehicule')
            ->setParameter('conducteur', $conducteur)
            ->setParameter('vehicule', $vehicule)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return null !== $result;
    }

    public function hasOverlap(Vehicule $vehicule, \DateTimeInterface $dateDebut, ?\DateTimeInterface $dateFin, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.vehicule = :vehicule')
            ->setParameter('vehicule', $vehicule);

        if (null !== $dateFin) {
            $qb->andWhere('a.dateDebut < :dateFin AND (a.dateFin IS NULL OR a.dateFin > :dateDebut)')
               ->setParameter('dateFin', $dateFin)
               ->setParameter('dateDebut', $dateDebut);
        } else {
            $qb->andWhere('a.dateFin IS NULL OR a.dateFin > :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        if (null !== $excludeId) {
            $qb->andWhere('a.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return count($qb->getQuery()->getResult()) > 0;
    }
}
