<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261126150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ISSUE-HIGH-05: avancement de l\'évaluation des portfolios (brouillon, validation, validateur)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portfolio ADD date_brouillon_evaluation DATETIME DEFAULT NULL, ADD date_validation_evaluation DATETIME DEFAULT NULL, ADD validateur_evaluation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062FE9D694A FOREIGN KEY (validateur_evaluation_id) REFERENCES enseignant (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_A9ED1062FE9D694A ON portfolio (validateur_evaluation_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED1062FE9D694A');
        $this->addSql('DROP INDEX IDX_A9ED1062FE9D694A ON portfolio');
        $this->addSql('ALTER TABLE portfolio DROP date_brouillon_evaluation, DROP date_validation_evaluation, DROP validateur_evaluation_id');
    }
}
