<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428200131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recréation de la table reponse avec la colonne commentaire';
    }

    public function up(Schema $schema): void
    {
        // Supprimer la table existante
        $this->addSql('DROP TABLE IF EXISTS reponse');

        // Recréer la table avec la colonne commentaire
        $this->addSql(<<<'SQL'
            CREATE TABLE reponse (
                id INT AUTO_INCREMENT NOT NULL,
                reclamation_id INT NOT NULL,
                description LONGTEXT NOT NULL,
                date DATETIME NOT NULL,
                rating INT NOT NULL,
                commentaire LONGTEXT DEFAULT NULL,
                PRIMARY KEY(id),
                CONSTRAINT FK_5FB6DEC7E62A5DB5 FOREIGN KEY (reclamation_id) REFERENCES reclamation (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS reponse');
    }
}
