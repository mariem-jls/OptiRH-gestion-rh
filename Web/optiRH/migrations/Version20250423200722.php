<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
<<<<<<<< HEAD:Web/optiRH/migrations/Version20250423200722.php
final class Version20250423200722 extends AbstractMigration
========
final class Version20250423195309 extends AbstractMigration
>>>>>>>> b288e91f0258e61f293f536c456abaeebff26b12:Web/optiRH/migrations/Version20250423195309.php
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE demande (id INT AUTO_INCREMENT NOT NULL, offre_id INT NOT NULL, statut VARCHAR(20) NOT NULL, date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, description LONGTEXT DEFAULT NULL, fichier_piece_jointe VARCHAR(255) DEFAULT NULL, nom_complet VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, date_debut_disponible DATE DEFAULT NULL, situation_actuelle VARCHAR(100) DEFAULT NULL, INDEX IDX_2694D7A54CC8505A (offre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE demande_matching (id INT AUTO_INCREMENT NOT NULL, demande_id INT NOT NULL, offre_id INT NOT NULL, cv_embedding JSON DEFAULT NULL, offre_embedding JSON DEFAULT NULL, matching_score DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_9A49BB4B80E95E18 (demande_id), INDEX IDX_9A49BB4B4CC8505A (offre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE evenement (id_evenement INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, lieu VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, prix DOUBLE PRECISION NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, image VARCHAR(255) NOT NULL, heure TIME NOT NULL, longitude DOUBLE PRECISION NOT NULL, latitude DOUBLE PRECISION NOT NULL, status VARCHAR(20) DEFAULT NULL, type VARCHAR(255) NOT NULL, modalite VARCHAR(255) NOT NULL, nbr_personnes INT DEFAULT NULL, PRIMARY KEY(id_evenement)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE favoris_evenement (id INT AUTO_INCREMENT NOT NULL, id_user_id INT DEFAULT NULL, id_evenement_id INT DEFAULT NULL, INDEX IDX_D95B32FD79F37AE5 (id_user_id), INDEX IDX_D95B32FD2C115A61 (id_evenement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE missions (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, created_by_id INT DEFAULT NULL, assigned_to INT DEFAULT NULL, titre VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, date_terminer DATETIME DEFAULT NULL, notified_late TINYINT(1) NOT NULL, INDEX IDX_34F1D47E166D1F9C (project_id), INDEX IDX_34F1D47EB03A8386 (created_by_id), INDEX IDX_34F1D47E89EEAF91 (assigned_to), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE offre (id INT AUTO_INCREMENT NOT NULL, poste VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, statut VARCHAR(50) DEFAULT NULL, date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP, mode_travail VARCHAR(50) DEFAULT NULL, type_contrat VARCHAR(50) DEFAULT NULL, localisation VARCHAR(100) DEFAULT NULL, niveau_experience VARCHAR(50) DEFAULT NULL, nb_postes INT DEFAULT NULL, date_expiration DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE projects (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_5C93B3A4B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reclamation (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, description LONGTEXT NOT NULL, status VARCHAR(50) NOT NULL, date DATETIME NOT NULL, type VARCHAR(50) NOT NULL, sentiment_score DOUBLE PRECISION DEFAULT NULL, sentiment_label VARCHAR(20) DEFAULT NULL, qr_code_filename VARCHAR(255) DEFAULT NULL, INDEX IDX_CE606404FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reclamation_archive (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, utilisateur_nom VARCHAR(255) NOT NULL, date DATETIME NOT NULL, deleted_at DATETIME NOT NULL, status VARCHAR(50) NOT NULL, sentiment_score DOUBLE PRECISION DEFAULT NULL, sentiment_label VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reponse (id INT AUTO_INCREMENT NOT NULL, reclamation_id INT NOT NULL, description LONGTEXT NOT NULL, date DATETIME NOT NULL, rating INT NOT NULL, INDEX IDX_5FB6DEC72D6BA2D9 (reclamation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reservation_evenement (id_participation INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, id_evenement INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(255) NOT NULL, date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_116109816B3CA4B (id_user), INDEX IDX_116109818B13D439 (id_evenement), PRIMARY KEY(id_participation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reservation_trajet (id INT AUTO_INCREMENT NOT NULL, vehicule_id INT NOT NULL, trajet_id INT NOT NULL, user_id INT NOT NULL, disponibilite VARCHAR(255) NOT NULL, INDEX IDX_63AAE0174A4A3511 (vehicule_id), INDEX IDX_63AAE017D12A823 (trajet_id), INDEX IDX_63AAE017A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE trajet (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, station VARCHAR(255) NOT NULL, depart VARCHAR(255) NOT NULL, arrive VARCHAR(255) NOT NULL, longitude_depart DOUBLE PRECISION NOT NULL, latitude_depart DOUBLE PRECISION NOT NULL, longitude_arrivee DOUBLE PRECISION NOT NULL, latitude_arrivee DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE vehicule (id INT AUTO_INCREMENT NOT NULL, trajet_id INT NOT NULL, disponibilite VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, nbrplace INT NOT NULL, nbr_reservation INT NOT NULL, INDEX IDX_292FFF1DD12A823 (trajet_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
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
            ALTER TABLE favoris_evenement ADD CONSTRAINT FK_D95B32FD79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)
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
            ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1DD12A823 FOREIGN KEY (trajet_id) REFERENCES trajet (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
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
            ALTER TABLE favoris_evenement DROP FOREIGN KEY FK_D95B32FD79F37AE5
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
            ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1DD12A823
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE demande
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE demande_matching
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE evenement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE favoris_evenement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE missions
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE offre
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE projects
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reclamation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reclamation_archive
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reponse
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reservation_evenement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reservation_trajet
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reset_password_request
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE trajet
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE vehicule
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
