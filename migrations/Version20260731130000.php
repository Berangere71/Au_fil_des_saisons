<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les signalements de recettes et les réponses aux commentaires.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recette ADD signale TINYINT NOT NULL DEFAULT 0, ADD motif_signalement LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE avis CHANGE note note INT DEFAULT NULL, ADD parent_avis_id INT DEFAULT NULL, ADD INDEX IDX_8F91ABF0DBF522D0 (parent_avis_id), ADD CONSTRAINT FK_8F91ABF0DBF522D0 FOREIGN KEY (parent_avis_id) REFERENCES avis (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0DBF522D0, DROP INDEX IDX_8F91ABF0DBF522D0, DROP parent_avis_id, CHANGE note note INT NOT NULL');
        $this->addSql('ALTER TABLE recette DROP signale, DROP motif_signalement');
    }
}
