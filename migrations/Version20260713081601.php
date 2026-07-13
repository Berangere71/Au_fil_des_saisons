<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713081601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, note INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, signale TINYINT NOT NULL, motif_signalement LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, recette_id INT NOT NULL, INDEX IDX_8F91ABF0A76ED395 (user_id), INDEX IDX_8F91ABF089312FE9 (recette_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE favoris (date_ajout DATETIME NOT NULL, user_id INT NOT NULL, recette_id INT NOT NULL, INDEX IDX_8933C432A76ED395 (user_id), INDEX IDX_8933C43289312FE9 (recette_id), UNIQUE INDEX uniq_favoris_user_recette (user_id, recette_id), PRIMARY KEY (user_id, recette_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE month (id INT AUTO_INCREMENT NOT NULL, name_month VARCHAR(20) NOT NULL, month_order INT NOT NULL, UNIQUE INDEX UNIQ_8EB6100640904243 (name_month), UNIQUE INDEX UNIQ_8EB610061A1A2C57 (month_order), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, category VARCHAR(255) NOT NULL, photo VARCHAR(255) DEFAULT NULL, conservation LONGTEXT NOT NULL, created_at DATETIME NOT NULL, debut_recolte_mois_id INT NOT NULL, fin_recolte_mois_id INT NOT NULL, INDEX IDX_D34A04AD4051FB51 (debut_recolte_mois_id), INDEX IDX_D34A04AD504CD5F1 (fin_recolte_mois_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_season (product_id INT NOT NULL, season_id INT NOT NULL, INDEX IDX_92981A0D4584665A (product_id), INDEX IDX_92981A0D4EC001D1 (season_id), PRIMARY KEY (product_id, season_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recette (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(150) NOT NULL, photo VARCHAR(255) DEFAULT NULL, type_plat VARCHAR(255) NOT NULL, nbr_person DOUBLE PRECISION NOT NULL, time_prepa INT DEFAULT NULL, ingredient LONGTEXT NOT NULL, preparation LONGTEXT NOT NULL, is_oven TINYINT NOT NULL, temp_oven DOUBLE PRECISION DEFAULT NULL, time_oven DOUBLE PRECISION DEFAULT NULL, is_public TINYINT NOT NULL, statut VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_49BB6390A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recette_product (recette_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_1732DED789312FE9 (recette_id), INDEX IDX_1732DED74584665A (product_id), PRIMARY KEY (recette_id, product_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recette_season (recette_id INT NOT NULL, season_id INT NOT NULL, INDEX IDX_E974995089312FE9 (recette_id), INDEX IDX_E97499504EC001D1 (season_id), PRIMARY KEY (recette_id, season_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE season (id INT AUTO_INCREMENT NOT NULL, name_season VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_F0E45BA9F69CC8E4 (name_season), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, prenom VARCHAR(100) NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, photo VARCHAR(255) DEFAULT NULL, role VARCHAR(255) NOT NULL, is_blocked TINYINT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF089312FE9 FOREIGN KEY (recette_id) REFERENCES recette (id)');
        $this->addSql('ALTER TABLE favoris ADD CONSTRAINT FK_8933C432A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE favoris ADD CONSTRAINT FK_8933C43289312FE9 FOREIGN KEY (recette_id) REFERENCES recette (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD4051FB51 FOREIGN KEY (debut_recolte_mois_id) REFERENCES month (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD504CD5F1 FOREIGN KEY (fin_recolte_mois_id) REFERENCES month (id)');
        $this->addSql('ALTER TABLE product_season ADD CONSTRAINT FK_92981A0D4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_season ADD CONSTRAINT FK_92981A0D4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recette ADD CONSTRAINT FK_49BB6390A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE recette_product ADD CONSTRAINT FK_1732DED789312FE9 FOREIGN KEY (recette_id) REFERENCES recette (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recette_product ADD CONSTRAINT FK_1732DED74584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recette_season ADD CONSTRAINT FK_E974995089312FE9 FOREIGN KEY (recette_id) REFERENCES recette (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recette_season ADD CONSTRAINT FK_E97499504EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0A76ED395');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF089312FE9');
        $this->addSql('ALTER TABLE favoris DROP FOREIGN KEY FK_8933C432A76ED395');
        $this->addSql('ALTER TABLE favoris DROP FOREIGN KEY FK_8933C43289312FE9');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD4051FB51');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD504CD5F1');
        $this->addSql('ALTER TABLE product_season DROP FOREIGN KEY FK_92981A0D4584665A');
        $this->addSql('ALTER TABLE product_season DROP FOREIGN KEY FK_92981A0D4EC001D1');
        $this->addSql('ALTER TABLE recette DROP FOREIGN KEY FK_49BB6390A76ED395');
        $this->addSql('ALTER TABLE recette_product DROP FOREIGN KEY FK_1732DED789312FE9');
        $this->addSql('ALTER TABLE recette_product DROP FOREIGN KEY FK_1732DED74584665A');
        $this->addSql('ALTER TABLE recette_season DROP FOREIGN KEY FK_E974995089312FE9');
        $this->addSql('ALTER TABLE recette_season DROP FOREIGN KEY FK_E97499504EC001D1');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE favoris');
        $this->addSql('DROP TABLE month');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_season');
        $this->addSql('DROP TABLE recette');
        $this->addSql('DROP TABLE recette_product');
        $this->addSql('DROP TABLE recette_season');
        $this->addSql('DROP TABLE season');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
