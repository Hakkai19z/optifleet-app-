<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Documents expirés ou expirant dans les $jours prochains jours.
     *
     * @return Document[]
     */
    public function findExpirant(int $jours = 30): array
    {
        $limite = (new \DateTime('today'))->modify("+{$jours} days");

        return $this->createQueryBuilder('d')
            ->leftJoin('d.vehicule', 'v')
            ->addSelect('v')
            ->where('d.dateExpiration <= :limite')
            ->setParameter('limite', $limite)
            ->orderBy('d.dateExpiration', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
