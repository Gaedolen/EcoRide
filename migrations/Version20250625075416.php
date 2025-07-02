<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250625075416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP FOREIGN KEY fk_covoiturage_utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP FOREIGN KEY fk_covoiturage_utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage CHANGE heure_arrivee heure_arrivee DATE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage ADD CONSTRAINT FK_28C79E89FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_covoiturage_utilisateur ON covoiturage
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_28C79E89FB88E14F ON covoiturage (utilisateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage ADD CONSTRAINT fk_covoiturage_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture ADD CONSTRAINT FK_E9E2810FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E9E2810FFB88E14F ON voiture (utilisateur_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP FOREIGN KEY FK_28C79E89FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP FOREIGN KEY FK_28C79E89FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage CHANGE heure_arrivee heure_arrivee VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage ADD CONSTRAINT fk_covoiturage_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_28c79e89fb88e14f ON covoiturage
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_covoiturage_utilisateur ON covoiturage (utilisateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage ADD CONSTRAINT FK_28C79E89FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture DROP FOREIGN KEY FK_E9E2810FFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_E9E2810FFB88E14F ON voiture
        SQL);
    }
}
