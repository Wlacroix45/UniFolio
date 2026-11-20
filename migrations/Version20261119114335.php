<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261119114335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ISSUE-CRIT-02: Ajout du rôle par département et contrainte d\'unicité sur enseignant_departement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE enseignant_departement ADD role VARCHAR(50) DEFAULT 'ROLE_EVALUATEUR' NOT NULL");
        $this->addSql('CREATE UNIQUE INDEX uniq_enseignant_departement ON enseignant_departement (enseignant_id, departement_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_enseignant_departement ON enseignant_departement');
        $this->addSql('ALTER TABLE enseignant_departement DROP role');
    }
}
