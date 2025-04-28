<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428195335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE interview (id INT AUTO_INCREMENT NOT NULL, demande_id INT NOT NULL, date_time DATETIME NOT NULL, google_meet_link VARCHAR(255) NOT NULL, INDEX IDX_CF1D3C3480E95E18 (demande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE interview ADD CONSTRAINT FK_CF1D3C3480E95E18 FOREIGN KEY (demande_id) REFERENCES demande (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notifications CHANGE recipient_id recipient_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offre CHANGE date_creation date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse ADD commentaire LONGTEXT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE interview DROP FOREIGN KEY FK_CF1D3C3480E95E18
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE interview
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notifications CHANGE recipient_id recipient_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offre CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse DROP commentaire
        SQL);
    }
}
