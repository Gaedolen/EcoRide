<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250728091520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE report (id INT AUTO_INCREMENT NOT NULL, reported_user_id INT NOT NULL, reported_by_id INT NOT NULL, message VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_C42F7784E7566E (reported_user_id), INDEX IDX_C42F778471CE806 (reported_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE report ADD CONSTRAINT FK_C42F7784E7566E FOREIGN KEY (reported_user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE report ADD CONSTRAINT FK_C42F778471CE806 FOREIGN KEY (reported_by_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE report DROP FOREIGN KEY FK_C42F7784E7566E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE report DROP FOREIGN KEY FK_C42F778471CE806
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE report
        SQL);
    }
}
