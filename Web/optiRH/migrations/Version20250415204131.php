<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250415204131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE evenement (id_evenement INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, lieu VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, prix DOUBLE PRECISION NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, image VARCHAR(255) NOT NULL, heure TIME NOT NULL, longitude DOUBLE PRECISION NOT NULL, latitude DOUBLE PRECISION NOT NULL, status VARCHAR(20) DEFAULT NULL, type VARCHAR(255) NOT NULL, modalite VARCHAR(255) NOT NULL, PRIMARY KEY(id_evenement)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reservation_evenement (id_participation INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, id_evenement INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, telephone VARCHAR(255) NOT NULL, date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_116109816B3CA4B (id_user), INDEX IDX_116109818B13D439 (id_evenement), PRIMARY KEY(id_participation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_evenement ADD CONSTRAINT FK_116109816B3CA4B FOREIGN KEY (id_user) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_evenement ADD CONSTRAINT FK_116109818B13D439 FOREIGN KEY (id_evenement) REFERENCES evenement (id_evenement) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions ADD created_by_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions ADD CONSTRAINT FK_34F1D47EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_34F1D47EB03A8386 ON missions (created_by_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offre CHANGE date_creation date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD type VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet DROP FOREIGN KEY FK_reservation_user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet DROP FOREIGN KEY FK_63AAE0174A4A3511
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet CHANGE user_id user_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet ADD CONSTRAINT FK_63AAE017A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet ADD CONSTRAINT FK_63AAE0174A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet RENAME INDEX fk_reservation_user TO IDX_63AAE017A76ED395
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_evenement DROP FOREIGN KEY FK_116109816B3CA4B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_evenement DROP FOREIGN KEY FK_116109818B13D439
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE evenement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reservation_evenement
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions DROP FOREIGN KEY FK_34F1D47EB03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_34F1D47EB03A8386 ON missions
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions DROP created_by_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offre CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP type
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet DROP FOREIGN KEY FK_63AAE017A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet DROP FOREIGN KEY FK_63AAE0174A4A3511
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet CHANGE user_id user_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet ADD CONSTRAINT FK_reservation_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet ADD CONSTRAINT FK_63AAE0174A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation_trajet RENAME INDEX idx_63aae017a76ed395 TO FK_reservation_user
        SQL);
    }
}
