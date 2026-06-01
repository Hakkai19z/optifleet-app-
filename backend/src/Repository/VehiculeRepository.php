<?php

namespace App\Repository;

use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicule::class);
    }

    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('v.immatriculation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatut(): array
    {
        return $this->createQueryBuilder('v')
            ->select('v.statut, COUNT(v.id) as total')
            ->groupBy('v.statut')
            ->getQuery()
            ->getResult();
    }
}
