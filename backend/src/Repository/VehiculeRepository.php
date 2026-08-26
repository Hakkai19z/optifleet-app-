<?php

namespace App\Repository;

use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicule>
 */
class VehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicule::class);
    }

    /**
     * @return Vehicule[]
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('v.immatriculation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{statut: string, total: int}>
     */
    public function countByStatut(): array
    {
        return $this->createQueryBuilder('v')
            ->select('v.statut, COUNT(v.id) as total')
            ->groupBy('v.statut')
            ->getQuery()
            ->getResult();
    }
}
