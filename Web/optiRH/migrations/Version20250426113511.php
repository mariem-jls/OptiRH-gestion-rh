<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426113511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE vehicule (id INT AUTO_INCREMENT NOT NULL, trajet_id INT NOT NULL, disponibilite VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, nbrplace INT NOT NULL, nbr_reservation INT NOT NULL, INDEX IDX_292FFF1DD12A823 (trajet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1DD12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande ADD CONSTRAINT FK_2694D7A54CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande_matching ADD CONSTRAINT FK_9A49BB4B80E95E18 FOREIGN KEY (demande_id) REFERENCES demande (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande_matching ADD CONSTRAINT FK_9A49BB4B4CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D95B32FD79F37AE5 ON favoris_evenement
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D95B32FD2C115A61 ON favoris_evenement
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris_evenement ADD id_user INT NOT NULL, ADD id_evenement INT NOT NULL, DROP id_user_id, DROP id_evenement_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris_evenement ADD CONSTRAINT FK_D95B32FD6B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris_evenement ADD CONSTRAINT FK_D95B32FD8B13D439 FOREIGN KEY (id_evenement) REFERENCES evenement (id_evenement) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D95B32FD6B3CA4B ON favoris_evenement (id_user)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D95B32FD8B13D439 ON favoris_evenement (id_evenement)
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
            ALTER TABLE user ADD avatar VARCHAR(255) DEFAULT NULL, ADD google_authenticator_secret VARCHAR(255) DEFAULT NULL, CHANGE roles roles JSON NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet DROP FOREIGN KEY FK_63AAE0174A4A3511
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1DD12A823
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE vehicule
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A54CC8505A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande_matching DROP FOREIGN KEY FK_9A49BB4B80E95E18
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande_matching DROP FOREIGN KEY FK_9A49BB4B4CC8505A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris_evenement DROP FOREIGN KEY FK_D95B32FD6B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris_evenement DROP FOREIGN KEY FK_D95B32FD8B13D439
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D95B32FD6B3CA4B ON favoris_evenement
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D95B32FD8B13D439 ON favoris_evenement
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris_evenement ADD id_user_id INT DEFAULT NULL, ADD id_evenement_id INT DEFAULT NULL, DROP id_user, DROP id_evenement
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D95B32FD79F37AE5 ON favoris_evenement (id_user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D95B32FD2C115A61 ON favoris_evenement (id_evenement_id)
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
            ALTER TABLE reservation_trajet DROP FOREIGN KEY FK_63AAE017D12A823
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet DROP FOREIGN KEY FK_63AAE017A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP avatar, DROP google_authenticator_secret, CHANGE roles roles JSON NOT NULL COLLATE `utf8mb4_bin`, CHANGE created_at created_at DATETIME NOT NULL
        SQL);
    }
}
