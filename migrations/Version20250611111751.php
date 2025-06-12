<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250611111751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE adresse adresse VARCHAR(50) DEFAULT NULL, CHANGE telephone telephone VARCHAR(50) DEFAULT NULL, CHANGE is_chauffeur is_chauffeur TINYINT(1) DEFAULT 0 NOT NULL, CHANGE is_passager is_passager TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture DROP FOREIGN KEY FK_E9E2810FFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_E9E2810FFB88E14F ON voiture
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture DROP utilisateur_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_IDENTIFIER_EMAIL ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user CHANGE adresse adresse VARCHAR(50) NOT NULL, CHANGE telephone telephone VARCHAR(50) NOT NULL, CHANGE is_chauffeur is_chauffeur TINYINT(1) DEFAULT 0, CHANGE is_passager is_passager TINYINT(1) DEFAULT 0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture ADD utilisateur_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture ADD CONSTRAINT FK_E9E2810FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E9E2810FFB88E14F ON voiture (utilisateur_id)
        SQL);
    }
}
