<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250819082510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE report_employe (id INT AUTO_INCREMENT NOT NULL, reported_user_id INT NOT NULL, reported_by_id INT NOT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, INDEX IDX_989DAEB9E7566E (reported_user_id), INDEX IDX_989DAEB971CE806 (reported_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE report_employe ADD CONSTRAINT FK_989DAEB9E7566E FOREIGN KEY (reported_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE report_employe ADD CONSTRAINT FK_989DAEB971CE806 FOREIGN KEY (reported_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report_employe DROP FOREIGN KEY FK_989DAEB9E7566E');
        $this->addSql('ALTER TABLE report_employe DROP FOREIGN KEY FK_989DAEB971CE806');
        $this->addSql('DROP TABLE report_employe');
    }
}
