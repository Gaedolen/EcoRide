<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250921153215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id SERIAL NOT NULL, auteur_id INT NOT NULL, cible_id INT NOT NULL, covoiturage_id INT NOT NULL, note INT NOT NULL, commentaire TEXT NOT NULL, date_avis TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, statut VARCHAR(30) NOT NULL, is_validated BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8F91ABF060BB6FE6 ON avis (auteur_id)');
        $this->addSql('CREATE INDEX IDX_8F91ABF0A96E5E09 ON avis (cible_id)');
        $this->addSql('CREATE INDEX IDX_8F91ABF062671590 ON avis (covoiturage_id)');
        $this->addSql('CREATE TABLE configuration (id SERIAL NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE covoiturage (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, voiture_id INT DEFAULT NULL, date_depart DATE NOT NULL, heure_depart TIME(0) WITHOUT TIME ZONE NOT NULL, lieu_depart VARCHAR(50) NOT NULL, date_arrivee DATE NOT NULL, heure_arrivee TIME(0) WITHOUT TIME ZONE NOT NULL, lieu_arrivee VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, nb_place INT NOT NULL, prix_personne DOUBLE PRECISION NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_28C79E89FB88E14F ON covoiturage (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_28C79E89181A8BA ON covoiturage (voiture_id)');
        $this->addSql('CREATE TABLE marque (id SERIAL NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE parametre (id SERIAL NOT NULL, propriete VARCHAR(50) NOT NULL, valeur VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE report (id SERIAL NOT NULL, reported_user_id INT NOT NULL, reported_by_id INT NOT NULL, covoiturage_id INT NOT NULL, message VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, statut VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C42F7784E7566E ON report (reported_user_id)');
        $this->addSql('CREATE INDEX IDX_C42F778471CE806 ON report (reported_by_id)');
        $this->addSql('CREATE INDEX IDX_C42F778462671590 ON report (covoiturage_id)');
        $this->addSql('COMMENT ON COLUMN report.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE reservation (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, covoiturage_id INT NOT NULL, date_reservation TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_42C84955FB88E14F ON reservation (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_42C8495562671590 ON reservation (covoiturage_id)');
        $this->addSql('CREATE TABLE role (id SERIAL NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, role_id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, pseudo VARCHAR(50) NOT NULL, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, adresse VARCHAR(50) DEFAULT NULL, telephone VARCHAR(50) DEFAULT NULL, photo BYTEA DEFAULT NULL, date_naissance DATE NOT NULL, is_chauffeur BOOLEAN DEFAULT false NOT NULL, is_passager BOOLEAN DEFAULT true NOT NULL, note DOUBLE PRECISION DEFAULT NULL, is_suspended BOOLEAN DEFAULT false NOT NULL, credits INT NOT NULL, suspend_reason VARCHAR(255) DEFAULT NULL, dernier_credit_jour DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8D93D649D60322AC ON "user" (role_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE TABLE voiture (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, modele VARCHAR(50) NOT NULL, immatriculation VARCHAR(50) NOT NULL, energie VARCHAR(50) NOT NULL, couleur VARCHAR(50) NOT NULL, date_premiere_immatriculation DATE NOT NULL, marque VARCHAR(50) NOT NULL, nb_places INT NOT NULL, fumeur BOOLEAN NOT NULL, animaux BOOLEAN NOT NULL, preferences JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E9E2810FFB88E14F ON voiture (utilisateur_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF060BB6FE6 FOREIGN KEY (auteur_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0A96E5E09 FOREIGN KEY (cible_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF062671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE covoiturage ADD CONSTRAINT FK_28C79E89FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE covoiturage ADD CONSTRAINT FK_28C79E89181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784E7566E FOREIGN KEY (reported_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F778471CE806 FOREIGN KEY (reported_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F778462671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495562671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES role (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE voiture ADD CONSTRAINT FK_E9E2810FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA heroku_ext');
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE avis DROP CONSTRAINT FK_8F91ABF060BB6FE6');
        $this->addSql('ALTER TABLE avis DROP CONSTRAINT FK_8F91ABF0A96E5E09');
        $this->addSql('ALTER TABLE avis DROP CONSTRAINT FK_8F91ABF062671590');
        $this->addSql('ALTER TABLE covoiturage DROP CONSTRAINT FK_28C79E89FB88E14F');
        $this->addSql('ALTER TABLE covoiturage DROP CONSTRAINT FK_28C79E89181A8BA');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F7784E7566E');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F778471CE806');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F778462671590');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C84955FB88E14F');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C8495562671590');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649D60322AC');
        $this->addSql('ALTER TABLE voiture DROP CONSTRAINT FK_E9E2810FFB88E14F');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE configuration');
        $this->addSql('DROP TABLE covoiturage');
        $this->addSql('DROP TABLE marque');
        $this->addSql('DROP TABLE parametre');
        $this->addSql('DROP TABLE report');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE voiture');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
