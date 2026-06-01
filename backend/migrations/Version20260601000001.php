<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quota_km_annuel column to vehicule table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicule ADD COLUMN quota_km_annuel INTEGER DEFAULT NULL');
        $this->addSql('UPDATE vehicule SET quota_km_annuel = 30000 WHERE quota_km_annuel IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vehicule DROP COLUMN quota_km_annuel');
    }
}
