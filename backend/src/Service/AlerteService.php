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

    /** Types d'alerte gérés automatiquement (échéances) — exclut les signalements manuels ('autre'). */
    private const TYPES_ECHEANCE = ['assurance', 'CT', 'revision', 'vidange'];

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
        // Conditions d'alerte actuellement vraies, indexées par "vehiculeId:type".
        $conditionsActives = [];

        foreach ($this->entretienRepository->findEchus() as $entretien) {
            $vehicule = $entretien->getVehicule();
            if (null === $vehicule) {
                continue;
            }
            $type = $this->mapTypeEntretien($entretien->getType());
            $conditionsActives[$this->cleCondition($vehicule, $type)] = true;

            if (! $this->alerteRepository->existsForVehiculeAndType($vehicule, $type)) {
                $this->creerAlerteEntretien($entretien, $type);
                ++$count;
            }
        }

        // Documents expirés ou expirant prochainement (assurance, CT, etc.)
        foreach ($this->documentRepository->findExpirant(self::DELAI_DOCUMENT_JOURS) as $document) {
            $vehicule = $document->getVehicule();
            if (null === $vehicule) {
                continue;
            }
            $type = $this->mapTypeDocument($document->getType());
            $conditionsActives[$this->cleCondition($vehicule, $type)] = true;

            if (! $this->alerteRepository->existsForVehiculeAndType($vehicule, $type)) {
                $this->creerAlerteDocument($document, $type);
                ++$count;
            }
        }

        // Auto-résolution : une alerte d'échéance dont la condition n'existe plus
        // (entretien réalisé, document renouvelé) passe à « resolue ». Les
        // signalements manuels (type 'autre') ne sont jamais touchés.
        $this->resoudreAlertesObsoletes($conditionsActives);

        $this->entityManager->flush();

        return $count;
    }

    private function cleCondition(Vehicule $vehicule, string $type): string
    {
        return $vehicule->getId() . ':' . $type;
    }

    /**
     * @param array<string, true> $conditionsActives
     */
    private function resoudreAlertesObsoletes(array $conditionsActives): void
    {
        foreach ($this->alerteRepository->findActiveAlertes() as $alerte) {
            if (! in_array($alerte->getType(), self::TYPES_ECHEANCE, true)) {
                continue;
            }
            $vehicule = $alerte->getVehicule();
            if (null === $vehicule) {
                continue;
            }
            if (! isset($conditionsActives[$this->cleCondition($vehicule, $alerte->getType())])) {
                $alerte->setStatut('resolue');
            }
        }
    }

    private function mapTypeEntretien(?string $typeEntretien): string
    {
        return match ($typeEntretien) {
            'CT' => 'CT',
            'vidange' => 'vidange',
            default => 'revision',
        };
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

    private function creerAlerteEntretien(Entretien $entretien, string $typeAlerte): void
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
        $alerte->setType($typeAlerte);
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
