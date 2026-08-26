<?php

namespace App\Repository;

use App\Entity\Entretien;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entretien>
 */
class EntretienRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entretien::class);
    }

    /**
     * @return Entretien[]
     */
    public function findEchus(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('e')
            ->leftJoin('e.vehicule', 'v')
            ->addSelect('v')
            ->where('e.dateProchaine < :now OR (e.kmProchaine IS NOT NULL AND e.kmProchaine < v.kilometrage)')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function findCoutMaintenanceByPeriod(\DateTimeInterface $debut, \DateTimeInterface $fin): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('SUM(e.cout) as total')
            ->where('e.dateRealise BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
