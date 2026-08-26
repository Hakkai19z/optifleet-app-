<?php

namespace App\Service;

use App\Entity\Alerte;
use App\Entity\Document;
use App\Entity\Entretien;
use App\Entity\Utilisateur;
use App\Entity\Vehicule;
use App\Repository\AlerteRepository;
use App\Repository\DocumentRepository;
use App\Repository\EntretienRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlerteService
{
    /** Nombre de jours avant expiration d'un document déclenchant une alerte. */
    private const DELAI_DOCUMENT_JOURS = 30;

    /** Types d'alerte gérés automatiquement (échéances) — exclut les signalements manuels ('autre'). */
    private const TYPES_ECHEANCE = ['assurance', 'CT', 'revision', 'vidange'];

    /** @var Utilisateur[]|null Cache des destinataires (gestionnaires + admins). */
    private ?array $gestionnairesCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AlerteRepository $alerteRepository,
        private readonly EntretienRepository $entretienRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly NotificationService $notification,
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

        $this->notifierGestionnaires($alerte);

        return $alerte;
    }

    public function verifierEcheances(): int
    {
        // Conditions d'alerte actuellement vraies, indexées par "vehiculeId:type".
        $conditionsActives = [];
        // Alertes réellement créées ce passage, à notifier une fois persistées.
        $nouvelles = [];

        foreach ($this->entretienRepository->findEchus() as $entretien) {
            $vehicule = $entretien->getVehicule();
            if (null === $vehicule) {
                continue;
            }
            $type = $this->mapTypeEntretien($entretien->getType());
            $conditionsActives[$this->cleCondition($vehicule, $type)] = true;

            if (! $this->alerteRepository->existsForVehiculeAndType($vehicule, $type)) {
                $nouvelles[] = $this->creerAlerteEntretien($entretien, $type);
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
                $nouvelles[] = $this->creerAlerteDocument($document, $type);
            }
        }

        // Auto-résolution : une alerte d'échéance dont la condition n'existe plus
        // (entretien réalisé, document renouvelé) passe à « resolue ». Les
        // signalements manuels (type 'autre') ne sont jamais touchés.
        $this->resoudreAlertesObsoletes($conditionsActives);

        $this->entityManager->flush();

        // Notification par courriel une fois les alertes réellement persistées.
        foreach ($nouvelles as $alerte) {
            $this->notifierGestionnaires($alerte);
        }

        return count($nouvelles);
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

    /**
     * Prévient les gestionnaires et administrateurs de la création d'une alerte.
     * La liste des destinataires est mise en cache pour éviter une requête par alerte.
     */
    private function notifierGestionnaires(Alerte $alerte): void
    {
        if (null === $this->gestionnairesCache) {
            $this->gestionnairesCache = $this->utilisateurRepository->findBy([
                'role' => ['GESTIONNAIRE', 'ADMIN'],
            ]);
        }

        $this->notification->notifierAlerte($alerte, $this->gestionnairesCache);
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

    private function creerAlerteDocument(Document $document, string $typeAlerte): Alerte
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

        return $alerte;
    }

    private function creerAlerteEntretien(Entretien $entretien, string $typeAlerte): Alerte
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

        return $alerte;
    }

    public function countActiveAlertes(): int
    {
        return count($this->alerteRepository->findActiveAlertes());
    }
}
