<?php

namespace App\Service;

use App\Entity\Alerte;
use App\Entity\Utilisateur;
use App\Entity\Vehicule;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Notifications par courriel. Volontairement tolérante aux pannes : l'échec
 * d'un envoi est journalisé mais ne fait jamais échouer l'opération métier
 * qui l'a déclenché (affectation, génération d'alerte).
 */
class NotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $fromAddress,
        private readonly string $fromName,
    ) {
    }

    public function notifierAffectation(Utilisateur $conducteur, Vehicule $vehicule): void
    {
        $this->envoyer(
            $conducteur->getEmail(),
            'OptiFleet — un véhicule vous a été affecté',
            sprintf(
                "Bonjour %s,\n\n"
                . "Le véhicule %s (%s %s) vous est affecté depuis le %s.\n\n"
                . 'Vous retrouvez son kilométrage, son quota annuel et son historique '
                . "d'entretien dans l'espace « Mon véhicule ».\n\n"
                . '— OptiFleet',
                $conducteur->getPrenom(),
                $vehicule->getImmatriculation(),
                $vehicule->getMarque(),
                $vehicule->getModele(),
                (new \DateTimeImmutable())->format('d/m/Y'),
            ),
        );
    }

    /**
     * @param iterable<Utilisateur> $destinataires
     */
    public function notifierAlerte(Alerte $alerte, iterable $destinataires): void
    {
        $vehicule = $alerte->getVehicule();
        $immat = null !== $vehicule ? $vehicule->getImmatriculation() : 'véhicule inconnu';
        $echeance = null !== $alerte->getDateEcheance()
            ? $alerte->getDateEcheance()->format('d/m/Y')
            : 'non datée';

        foreach ($destinataires as $destinataire) {
            $this->envoyer(
                $destinataire->getEmail(),
                sprintf('OptiFleet — alerte %s sur %s', $alerte->getType(), $immat),
                sprintf(
                    "Bonjour %s,\n\n%s\n\nVéhicule : %s\nÉchéance : %s\n\n— OptiFleet",
                    $destinataire->getPrenom(),
                    $alerte->getMessage(),
                    $immat,
                    $echeance,
                ),
            );
        }
    }

    private function envoyer(?string $destinataire, string $sujet, string $corps): void
    {
        if (null === $destinataire || '' === $destinataire) {
            return;
        }

        $email = (new Email())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($destinataire)
            ->subject($sujet)
            ->text($corps);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Envoi de notification impossible', [
                'destinataire' => $destinataire,
                'sujet' => $sujet,
                'erreur' => $e->getMessage(),
            ]);
        }
    }
}
