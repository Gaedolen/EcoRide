<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250515072348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correction : suppression de la création de la table user (déjà existante)';
    }

    public function up(Schema $schema): void
    {
        // On ne recrée pas la table 'user' car elle existe déjà

        // Modification des champs dans la table 'covoiturage'
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage CHANGE heure_depart heure_depart DATE NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // On ne supprime pas la table 'user' car elle existait déjà

        // Rétablit les anciens types dans la table 'covoiturage' si on annule la migration
        $this->addSql(<<<'SQL'
            ALTER TABLE covoiturage CHANGE heure_depart heure_depart TIME NOT NULL, CHANGE statut statut VARCHAR(50) DEFAULT NULL
        SQL);
    }
}
