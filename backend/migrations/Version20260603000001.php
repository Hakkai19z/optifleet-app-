<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add plein (fuel), reservation and document tables';
    }

    public function up(Schema $schema): void
    {
        // Carburant
        $this->addSql('CREATE TABLE plein (
            id SERIAL PRIMARY KEY,
            vehicule_id INTEGER NOT NULL,
            date DATE NOT NULL,
            litres NUMERIC(8, 2) NOT NULL,
            prix_litre NUMERIC(6, 3) NOT NULL,
            kilometrage INTEGER NOT NULL,
            type_carburant VARCHAR(20) NOT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT fk_plein_vehicule FOREIGN KEY (vehicule_id) REFERENCES vehicule (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_plein_vehicule ON plein (vehicule_id)');

        // Réservations
        $this->addSql('CREATE TABLE reservation (
            id SERIAL PRIMARY KEY,
            vehicule_id INTEGER NOT NULL,
            conducteur_id INTEGER NOT NULL,
            date_debut TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            date_fin TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            statut VARCHAR(20) NOT NULL,
            motif TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT fk_reservation_vehicule FOREIGN KEY (vehicule_id) REFERENCES vehicule (id) ON DELETE CASCADE,
            CONSTRAINT fk_reservation_conducteur FOREIGN KEY (conducteur_id) REFERENCES utilisateur (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_reservation_vehicule ON reservation (vehicule_id)');
        $this->addSql('CREATE INDEX idx_reservation_conducteur ON reservation (conducteur_id)');

        // Documents
        $this->addSql('CREATE TABLE document (
            id SERIAL PRIMARY KEY,
            vehicule_id INTEGER NOT NULL,
            type VARCHAR(30) NOT NULL,
            numero VARCHAR(100) DEFAULT NULL,
            date_delivrance DATE DEFAULT NULL,
            date_expiration DATE NOT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT fk_document_vehicule FOREIGN KEY (vehicule_id) REFERENCES vehicule (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX idx_document_vehicule ON document (vehicule_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE plein');
    }
}
