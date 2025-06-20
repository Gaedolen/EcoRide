<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250619134451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage ADD voiture_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage ADD CONSTRAINT FK_28C79E89181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_28C79E89181A8BA ON covoiturage (voiture_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture DROP FOREIGN KEY fk_voiture_utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_voiture_utilisateur ON voiture
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture DROP utilisateur_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP FOREIGN KEY FK_28C79E89181A8BA
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_28C79E89181A8BA ON covoiturage
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP voiture_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture ADD utilisateur_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture ADD CONSTRAINT fk_voiture_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_voiture_utilisateur ON voiture (utilisateur_id)
        SQL);
    }
}
