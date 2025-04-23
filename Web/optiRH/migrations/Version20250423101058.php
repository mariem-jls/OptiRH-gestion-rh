<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250423101058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE demande_matching (id INT AUTO_INCREMENT NOT NULL, demande_id INT NOT NULL, offre_id INT NOT NULL, cv_embedding JSON DEFAULT NULL, offre_embedding JSON DEFAULT NULL, matching_score DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_9A49BB4B80E95E18 (demande_id), INDEX IDX_9A49BB4B4CC8505A (offre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reclamation_archive (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, utilisateur_nom VARCHAR(255) NOT NULL, date DATETIME NOT NULL, deleted_at DATETIME NOT NULL, status VARCHAR(50) NOT NULL, sentiment_score DOUBLE PRECISION DEFAULT NULL, sentiment_label VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande_matching ADD CONSTRAINT FK_9A49BB4B80E95E18 FOREIGN KEY (demande_id) REFERENCES demande (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande_matching ADD CONSTRAINT FK_9A49BB4B4CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evenement ADD nbr_personnes INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions ADD notified_late TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offre CHANGE date_creation date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE demande_matching DROP FOREIGN KEY FK_9A49BB4B80E95E18
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE demande_matching DROP FOREIGN KEY FK_9A49BB4B4CC8505A
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE demande_matching
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reclamation_archive
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evenement DROP nbr_personnes
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions DROP notified_late
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offre CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
    }
}
