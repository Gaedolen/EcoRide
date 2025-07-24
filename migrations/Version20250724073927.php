<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250724073927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP FOREIGN KEY reservation_ibfk_2
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reservation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis DROP FOREIGN KEY fk_avis_auteur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis DROP FOREIGN KEY fk_avis_cible
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis DROP statut, CHANGE commentaire commentaire LONGTEXT NOT NULL, CHANGE note note INT NOT NULL, CHANGE date_avis date_avis DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_avis_auteur ON avis
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8F91ABF060BB6FE6 ON avis (auteur_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX fk_avis_cible ON avis
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8F91ABF0A96E5E09 ON avis (cible_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis ADD CONSTRAINT fk_avis_auteur FOREIGN KEY (auteur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis ADD CONSTRAINT fk_avis_cible FOREIGN KEY (cible_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP FOREIGN KEY fk_covoiturage_utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP FOREIGN KEY fk_covoiturage_utilisateur
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage CHANGE heure_depart heure_depart TIME NOT NULL, CHANGE heure_arrivee heure_arrivee TIME NOT NULL
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
            ALTER TABLE user ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES role (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D649D60322AC ON user (role_id)
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
            CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, covoiturage_id INT NOT NULL, date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX covoiturage_id (covoiturage_id), UNIQUE INDEX unique_reservation (utilisateur_id, covoiturage_id), INDEX IDX_42C84955FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_1 FOREIGN KEY (utilisateur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD CONSTRAINT reservation_ibfk_2 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF060BB6FE6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0A96E5E09
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis ADD statut VARCHAR(50) NOT NULL, CHANGE note note VARCHAR(50) NOT NULL, CHANGE commentaire commentaire VARCHAR(50) NOT NULL, CHANGE date_avis date_avis DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_8f91abf060bb6fe6 ON avis
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_avis_auteur ON avis (auteur_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_8f91abf0a96e5e09 ON avis
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX fk_avis_cible ON avis (cible_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF060BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0A96E5E09 FOREIGN KEY (cible_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP FOREIGN KEY FK_28C79E89FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage DROP FOREIGN KEY FK_28C79E89FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage CHANGE heure_depart heure_depart TIME DEFAULT NULL, CHANGE heure_arrivee heure_arrivee TIME DEFAULT NULL
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
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D649D60322AC
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D649D60322AC ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE voiture DROP FOREIGN KEY FK_E9E2810FFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_E9E2810FFB88E14F ON voiture
        SQL);
    }
}
