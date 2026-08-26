<?php

namespace App\Repository;

use App\Entity\Plein;
use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plein>
 */
class PleinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plein::class);
    }

    /**
     * Pleins d'un véhicule classés par kilométrage croissant (pour calcul de conso).
     *
     * @return Plein[]
     */
    public function findByVehiculeOrdered(Vehicule $vehicule): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.vehicule = :v')
            ->setParameter('v', $vehicule)
            ->orderBy('p.kilometrage', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getCoutCarburantByPeriod(\DateTimeInterface $debut, \DateTimeInterface $fin): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.litres * p.prixLitre) as total')
            ->where('p.date BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Consommation moyenne (L/100km) d'un véhicule, calculée sur les pleins successifs.
     */
    public function getConsommationMoyenne(Vehicule $vehicule): ?float
    {
        $pleins = $this->findByVehiculeOrdered($vehicule);
        if (count($pleins) < 2) {
            return null;
        }

        $premier = $pleins[0];
        $dernier = $pleins[count($pleins) - 1];
        $distance = $dernier->getKilometrage() - $premier->getKilometrage();
        if ($distance <= 0) {
            return null;
        }

        // On exclut le premier plein : ses litres ont rempli le réservoir avant la distance mesurée.
        $litresConsommes = 0.0;
        foreach (array_slice($pleins, 1) as $plein) {
            $litresConsommes += (float) $plein->getLitres();
        }

        return round(($litresConsommes / $distance) * 100, 1);
    }
}
