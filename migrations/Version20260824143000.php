<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260824143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l’archivage administrateur des commentaires (avis).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis ADD archived_by_admin TINYINT(1) NOT NULL DEFAULT 0, ADD archived_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis DROP archived_by_admin, DROP archived_at');
    }
}
