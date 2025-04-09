<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250409061634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE missions (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, assigned_to INT DEFAULT NULL, titre VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, date_terminer DATETIME DEFAULT NULL, INDEX IDX_34F1D47E166D1F9C (project_id), INDEX IDX_34F1D47E89EEAF91 (assigned_to), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE projects (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, status VARCHAR(20) DEFAULT NULL, INDEX IDX_5C93B3A4B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, address VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions ADD CONSTRAINT FK_34F1D47E166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions ADD CONSTRAINT FK_34F1D47E89EEAF91 FOREIGN KEY (assigned_to) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE missions DROP FOREIGN KEY FK_34F1D47E166D1F9C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE missions DROP FOREIGN KEY FK_34F1D47E89EEAF91
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE projects DROP FOREIGN KEY FK_5C93B3A4B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE missions
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE projects
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }
}
