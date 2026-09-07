<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les index utilisés par les catalogues, profils et écrans de modération.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_product_category_name ON product (category, nom)');
        $this->addSql('CREATE INDEX idx_recette_publication ON recette (statut, is_public, created_at)');
        $this->addSql('CREATE INDEX idx_recette_reported ON recette (signale, created_at)');
        $this->addSql('CREATE INDEX idx_recette_user_created ON recette (user_id, created_at)');
        $this->addSql('CREATE INDEX idx_avis_reported_created ON avis (signale, created_at)');
        $this->addSql('CREATE INDEX idx_avis_user_reported ON avis (user_id, signale)');
        $this->addSql('CREATE INDEX idx_user_admin_filters ON user (role, is_blocked, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_product_category_name ON product');
        $this->addSql('DROP INDEX idx_recette_publication ON recette');
        $this->addSql('DROP INDEX idx_recette_reported ON recette');
        $this->addSql('DROP INDEX idx_recette_user_created ON recette');
        $this->addSql('DROP INDEX idx_avis_reported_created ON avis');
        $this->addSql('DROP INDEX idx_avis_user_reported ON avis');
        $this->addSql('DROP INDEX idx_user_admin_filters ON user');
    }
}
