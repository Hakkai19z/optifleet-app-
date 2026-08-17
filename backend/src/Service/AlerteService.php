<?php

namespace App\Service;

use App\Entity\Alerte;
use App\Entity\Document;
use App\Entity\Entretien;
use App\Entity\Vehicule;
use App\Repository\AlerteRepository;
use App\Repository\DocumentRepository;
use App\Repository\EntretienRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlerteService
{
    /** Nombre de jours avant expiration d'un document déclenchant une alerte. */
    private const DELAI_DOCUMENT_JOURS = 30;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AlerteRepository $alerteRepository,
        private readonly EntretienRepository $entretienRepository,
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    public function creerAlerte(Vehicule $vehicule, string $type, string $message, \DateTimeInterface $dateEcheance): Alerte
    {
        $alerte = new Alerte();
        $alerte->setVehicule($vehicule);
        $alerte->setType($type);
        $alerte->setMessage($message);
        $alerte->setDateEcheance($dateEcheance);
        $alerte->setStatut('en_attente');

        $this->entityManager->persist($alerte);
        $this->entityManager->flush();

        return $alerte;
    }

    public function verifierEcheances(): int
    {
        $count = 0;
        $entretienEchus = $this->entretienRepository->findEchus();

        foreach ($entretienEchus as $entretien) {
            if (! $this->alerteRepository->existsForVehiculeAndType($entretien->getVehicule(), $entretien->getType())) {
                $this->creerAlerteEntretien($entretien);
                ++$count;
            }
        }

        // Documents expirés ou expirant prochainement (assurance, CT, etc.)
        $documentsExpirant = $this->documentRepository->findExpirant(self::DELAI_DOCUMENT_JOURS);

        foreach ($documentsExpirant as $document) {
            $vehicule = $document->getVehicule();
            if (null === $vehicule) {
                continue;
            }
            $typeAlerte = $this->mapTypeDocument($document->getType());
            if (! $this->alerteRepository->existsForVehiculeAndType($vehicule, $typeAlerte)) {
                $this->creerAlerteDocument($document, $typeAlerte);
                ++$count;
            }
        }

        $this->entityManager->flush();

        return $count;
    }

    /** Convertit un type de document en type d'alerte autorisé. */
    private function mapTypeDocument(?string $typeDocument): string
    {
        return match ($typeDocument) {
            'assurance' => 'assurance',
            'controle_technique' => 'CT',
            default => 'autre',
        };
    }

    private function creerAlerteDocument(Document $document, string $typeAlerte): void
    {
        $vehicule = $document->getVehicule();
        $jours = $document->getJoursAvantExpiration();
        $echeance = $document->isExpire()
            ? sprintf('expiré depuis %d jour(s)', abs((int) $jours))
            : sprintf('expire dans %d jour(s)', (int) $jours);

        $message = sprintf(
            'Document %s du véhicule %s %s (%s) %s',
            $document->getType(),
            $vehicule->getMarque(),
            $vehicule->getModele(),
            $vehicule->getImmatriculation(),
            $echeance
        );

        $alerte = new Alerte();
        $alerte->setVehicule($vehicule);
        $alerte->setType($typeAlerte);
        $alerte->setMessage($message);
        $alerte->setDateEcheance($document->getDateExpiration() ?? new \DateTime());
        $alerte->setStatut('en_attente');

        $this->entityManager->persist($alerte);
    }

    private function creerAlerteEntretien(Entretien $entretien): void
    {
        $vehicule = $entretien->getVehicule();
        $message = sprintf(
            'Entretien %s échu pour le véhicule %s %s (%s)',
            $entretien->getType(),
            $vehicule->getMarque(),
            $vehicule->getModele(),
            $vehicule->getImmatriculation()
        );

        $dateEcheance = $entretien->getDateProchaine() ?? new \DateTime();

        $alerte = new Alerte();
        $alerte->setVehicule($vehicule);
        $alerte->setType('CT' === $entretien->getType() ? 'CT' : ('vidange' === $entretien->getType() ? 'vidange' : 'revision'));
        $alerte->setMessage($message);
        $alerte->setDateEcheance($dateEcheance);
        $alerte->setStatut('en_attente');

        $this->entityManager->persist($alerte);
    }

    public function countActiveAlertes(): int
    {
        return count($this->alerteRepository->findActiveAlertes());
    }
}
