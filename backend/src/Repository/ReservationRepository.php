<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Réservations non annulées d'un véhicule qui chevauchent le créneau donné.
     *
     * @return Reservation[]
     */
    public function findChevauchements(
        Vehicule $vehicule,
        \DateTimeInterface $debut,
        \DateTimeInterface $fin,
        ?int $excludeId = null,
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->where('r.vehicule = :vehicule')
            ->andWhere('r.statut != :annulee')
            // Deux créneaux se chevauchent si début_a < fin_b ET début_b < fin_a
            ->andWhere('r.dateDebut < :fin AND r.dateFin > :debut')
            ->setParameter('vehicule', $vehicule)
            ->setParameter('annulee', 'annulee')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin);

        if ($excludeId !== null) {
            $qb->andWhere('r.id != :id')->setParameter('id', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Réservations à venir, classées par date de début.
     *
     * @return Reservation[]
     */
    public function findAVenir(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.dateFin >= :now')
            ->andWhere('r.statut != :annulee')
            ->setParameter('now', new \DateTime())
            ->setParameter('annulee', 'annulee')
            ->orderBy('r.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
