<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial OptiFleet database schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categorie (
            id SERIAL NOT NULL,
            libelle VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            PRIMARY KEY(id),
            CONSTRAINT unique_libelle UNIQUE (libelle)
        )');

        $this->addSql('CREATE TABLE utilisateur (
            id SERIAL NOT NULL,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            mot_de_passe VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT \'CONDUCTEUR\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT unique_email UNIQUE (email),
            CONSTRAINT check_role CHECK (role IN (\'ADMIN\', \'GESTIONNAIRE\', \'CONDUCTEUR\'))
        )');
        $this->addSql('COMMENT ON COLUMN utilisateur.created_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE vehicule (
            id SERIAL NOT NULL,
            categorie_id INT DEFAULT NULL,
            immatriculation VARCHAR(20) NOT NULL,
            marque VARCHAR(100) NOT NULL,
            modele VARCHAR(100) NOT NULL,
            annee SMALLINT NOT NULL,
            kilometrage INT NOT NULL DEFAULT 0,
            statut VARCHAR(20) NOT NULL DEFAULT \'disponible\',
            latitude NUMERIC(10, 8) DEFAULT NULL,
            longitude NUMERIC(11, 8) DEFAULT NULL,
            adresse TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id),
            CONSTRAINT unique_immatriculation UNIQUE (immatriculation),
            CONSTRAINT check_statut CHECK (statut IN (\'disponible\', \'en_mission\', \'maintenance\', \'inactif\')),
            CONSTRAINT check_kilometrage CHECK (kilometrage >= 0)
        )');
        $this->addSql('COMMENT ON COLUMN vehicule.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN vehicule.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT fk_vehicule_categorie FOREIGN KEY (categorie_id) REFERENCES categorie (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_vehicule_categorie ON vehicule (categorie_id)');
        $this->addSql('CREATE INDEX idx_vehicule_statut ON vehicule (statut)');

        $this->addSql('CREATE TABLE affectation (
            id SERIAL NOT NULL,
            conducteur_id INT NOT NULL,
            vehicule_id INT NOT NULL,
            date_debut TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            date_fin TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            commentaire TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('COMMENT ON COLUMN affectation.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT fk_affectation_conducteur FOREIGN KEY (conducteur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE affectation ADD CONSTRAINT fk_affectation_vehicule FOREIGN KEY (vehicule_id) REFERENCES vehicule (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_affectation_conducteur ON affectation (conducteur_id)');
        $this->addSql('CREATE INDEX idx_affectation_vehicule ON affectation (vehicule_id)');

        $this->addSql('CREATE TABLE entretien (
            id SERIAL NOT NULL,
            vehicule_id INT NOT NULL,
            type VARCHAR(20) NOT NULL,
            date_realise DATE NOT NULL,
            date_prochaine DATE DEFAULT NULL,
            km_prochaine INT DEFAULT NULL,
            cout NUMERIC(10, 2) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT check_type CHECK (type IN (\'revision\', \'vidange\', \'CT\', \'freins\', \'pneus\', \'autre\'))
        )');
        $this->addSql('COMMENT ON COLUMN entretien.date_realise IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN entretien.date_prochaine IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN entretien.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE entretien ADD CONSTRAINT fk_entretien_vehicule FOREIGN KEY (vehicule_id) REFERENCES vehicule (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_entretien_vehicule ON entretien (vehicule_id)');

        $this->addSql('CREATE TABLE alerte (
            id SERIAL NOT NULL,
            vehicule_id INT NOT NULL,
            type VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            date_echeance DATE NOT NULL,
            statut VARCHAR(20) NOT NULL DEFAULT \'en_attente\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id),
            CONSTRAINT check_type_alerte CHECK (type IN (\'assurance\', \'CT\', \'revision\', \'vidange\', \'autre\')),
            CONSTRAINT check_statut_alerte CHECK (statut IN (\'en_attente\', \'en_cours\', \'resolue\'))
        )');
        $this->addSql('COMMENT ON COLUMN alerte.date_echeance IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN alerte.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE alerte ADD CONSTRAINT fk_alerte_vehicule FOREIGN KEY (vehicule_id) REFERENCES vehicule (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_alerte_vehicule ON alerte (vehicule_id)');
        $this->addSql('CREATE INDEX idx_alerte_statut ON alerte (statut)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS alerte');
        $this->addSql('DROP TABLE IF EXISTS entretien');
        $this->addSql('DROP TABLE IF EXISTS affectation');
        $this->addSql('DROP TABLE IF EXISTS vehicule');
        $this->addSql('DROP TABLE IF EXISTS utilisateur');
        $this->addSql('DROP TABLE IF EXISTS categorie');
    }
}
