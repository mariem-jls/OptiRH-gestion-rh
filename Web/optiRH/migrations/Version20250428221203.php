<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428221203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE notifications CHANGE recipient_id recipient_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offre CHANGE date_creation date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD document_name VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse RENAME INDEX fk_5fb6dec7e62a5db5 TO IDX_5FB6DEC72D6BA2D9
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE notifications CHANGE recipient_id recipient_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE offre CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP document_name, DROP updated_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reponse RENAME INDEX idx_5fb6dec72d6ba2d9 TO FK_5FB6DEC7E62A5DB5
        SQL);
    }
}
