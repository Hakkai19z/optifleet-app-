<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Journal d'audit (EF09 / OWASP A09) et champs RGPD sur utilisateur
 * (dernière connexion, horodatage d'anonymisation).
 */
final class Version20260801000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Journal d\'audit + champs RGPD (derniere_connexion_a, anonymise_a) sur utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE journal_audit (
            id SERIAL PRIMARY KEY,
            action VARCHAR(50) NOT NULL,
            cible VARCHAR(255) DEFAULT NULL,
            auteur_email VARCHAR(255) DEFAULT NULL,
            auteur_role VARCHAR(50) DEFAULT NULL,
            adresse_ip VARCHAR(45) DEFAULT NULL,
            details TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');
        $this->addSql('CREATE INDEX idx_audit_action ON journal_audit (action)');
        $this->addSql('CREATE INDEX idx_audit_created ON journal_audit (created_at)');

        $this->addSql('ALTER TABLE utilisateur ADD derniere_connexion_a TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD anonymise_a TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE journal_audit');
        $this->addSql('ALTER TABLE utilisateur DROP derniere_connexion_a');
        $this->addSql('ALTER TABLE utilisateur DROP anonymise_a');
    }
}
