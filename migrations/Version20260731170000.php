<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute une saison principale unique aux recettes tout en conservant les autres saisons associées.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recette ADD primary_season_id INT DEFAULT NULL, ADD INDEX IDX_49BB6390B4E3516E (primary_season_id)');
        $this->addSql('UPDATE recette r SET primary_season_id = (SELECT MIN(rs.season_id) FROM recette_season rs WHERE rs.recette_id = r.id)');
        $this->addSql('ALTER TABLE recette ADD CONSTRAINT FK_49BB6390B4E3516E FOREIGN KEY (primary_season_id) REFERENCES season (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recette DROP FOREIGN KEY FK_49BB6390B4E3516E, DROP INDEX IDX_49BB6390B4E3516E, DROP primary_season_id');
    }
}
