<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250425092551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris_evenement ADD CONSTRAINT FK_D95B32FD2C115A61 FOREIGN KEY (id_evenement_id) REFERENCES evenement (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions ADD CONSTRAINT FK_34F1D47E166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions ADD CONSTRAINT FK_34F1D47EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions ADD CONSTRAINT FK_34F1D47E89EEAF91 FOREIGN KEY (assigned_to) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3E92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offre CHANGE date_creation date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC72D6BA2D9 FOREIGN KEY (reclamation_id) REFERENCES reclamation (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_evenement ADD CONSTRAINT FK_116109816B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_evenement ADD CONSTRAINT FK_116109818B13D439 FOREIGN KEY (id_evenement) REFERENCES evenement (id_evenement) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet ADD CONSTRAINT FK_63AAE0174A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet ADD CONSTRAINT FK_63AAE017D12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet ADD CONSTRAINT FK_63AAE017A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE created_at created_at DATETIME DEFAULT 'now()' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1DD12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, headers LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, queue_name VARCHAR(190) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris_evenement DROP FOREIGN KEY FK_D95B32FD2C115A61
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions DROP FOREIGN KEY FK_34F1D47E166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions DROP FOREIGN KEY FK_34F1D47EB03A8386
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions DROP FOREIGN KEY FK_34F1D47E89EEAF91
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3E92F8F78
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offre CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC72D6BA2D9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_evenement DROP FOREIGN KEY FK_116109816B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_evenement DROP FOREIGN KEY FK_116109818B13D439
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet DROP FOREIGN KEY FK_63AAE0174A4A3511
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet DROP FOREIGN KEY FK_63AAE017D12A823
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet DROP FOREIGN KEY FK_63AAE017A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1DD12A823
        SQL);
    }
}
