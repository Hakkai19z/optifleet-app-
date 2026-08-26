<?php

namespace App\Command;

use App\Entity\Utilisateur;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Anonymise les comptes conducteurs inactifs depuis plus de trois ans,
 * conformément à l'engagement RGPD de limitation de conservation. À planifier
 * (cron) ; l'option --dry-run permet un contrôle sans modification.
 */
#[AsCommand(
    name: 'app:purge-comptes-inactifs',
    description: 'Anonymise les comptes conducteurs inactifs depuis plus de 3 ans (RGPD).',
)]
final class PurgeComptesInactifsCommand extends Command
{
    private const ANNEES_INACTIVITE = 3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly AuditLogger $audit,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les comptes concernés sans les anonymiser.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $seuil = new \DateTimeImmutable(sprintf('-%d years', self::ANNEES_INACTIVITE));

        $comptes = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(Utilisateur::class, 'u')
            ->where('u.anonymiseA IS NULL')
            ->andWhere('u.role = :role')
            ->andWhere('(u.derniereConnexionA IS NOT NULL AND u.derniereConnexionA < :seuil) OR (u.derniereConnexionA IS NULL AND u.createdAt < :seuil)')
            ->setParameter('role', 'CONDUCTEUR')
            ->setParameter('seuil', $seuil)
            ->getQuery()
            ->getResult();

        if (0 === count($comptes)) {
            $io->success('Aucun compte inactif à anonymiser.');

            return Command::SUCCESS;
        }

        foreach ($comptes as $compte) {
            $io->writeln(sprintf(' - %s (dernière activité : %s)', $compte->getUserIdentifier(),
                $compte->getDerniereConnexionA()?->format('d/m/Y') ?? 'jamais connecté'));

            if (! $dryRun) {
                $email = $compte->getUserIdentifier();
                $compte->setNom('Utilisateur');
                $compte->setPrenom('anonymisé');
                $compte->setEmail('anonyme-' . $compte->getId() . '@rgpd.invalid');
                $compte->setMotDePasse($this->hasher->hashPassword($compte, bin2hex(random_bytes(16))));
                $compte->setAnonymiseA(new \DateTimeImmutable());
                $this->audit->enregistrer('purge_rgpd', $email, 'Anonymisation automatique (inactivité > 3 ans)', $email);
            }
        }

        if ($dryRun) {
            $io->note(sprintf('%d compte(s) seraient anonymisés. Relancez sans --dry-run pour appliquer.', count($comptes)));

            return Command::SUCCESS;
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d compte(s) anonymisés.', count($comptes)));

        return Command::SUCCESS;
    }
}
