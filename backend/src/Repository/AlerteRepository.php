<?php

namespace App\Repository;

use App\Entity\Alerte;
use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AlerteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alerte::class);
    }

    public function findActiveAlertes(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.statut IN (:statuts)')
            ->setParameter('statuts', ['en_attente', 'en_cours'])
            ->orderBy('a.dateEcheance', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function existsForVehiculeAndType(Vehicule $vehicule, string $type): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.vehicule = :vehicule')
            ->andWhere('a.type = :type')
            ->andWhere('a.statut != :statut')
            ->setParameter('vehicule', $vehicule)
            ->setParameter('type', $type)
            ->setParameter('statut', 'resolue')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
