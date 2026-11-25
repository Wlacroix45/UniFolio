<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ISSUE-HIGH-02 (#39): Suppression entite V1 templates, creation portfolio_template/template_section et support snapshot sur portfolio/page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE portfolio_template (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, statut VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, departement_id INT NOT NULL, apc_referentiel_id INT DEFAULT NULL, annee_id INT DEFAULT NULL, apc_parcours_id INT DEFAULT NULL, enseignant_id INT DEFAULT NULL, INDEX IDX_EBEA451BCCF9E01E (departement_id), INDEX IDX_EBEA451B9048A9AB (apc_referentiel_id), INDEX IDX_EBEA451B543EC5F0 (annee_id), INDEX IDX_EBEA451B3E102C73 (apc_parcours_id), INDEX IDX_EBEA451BE455FCC0 (enseignant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE template_section (id INT AUTO_INCREMENT NOT NULL, ordre INT NOT NULL, titre VARCHAR(255) NOT NULL, consigne LONGTEXT DEFAULT NULL, question_reflexive_prompt LONGTEXT DEFAULT NULL, portfolio_template_id INT NOT NULL, INDEX IDX_7ECB01D6BBE3396C (portfolio_template_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE portfolio_template_apc_niveau (portfolio_template_id INT NOT NULL, apc_niveau_id INT NOT NULL, INDEX IDX_BB45E572BBE3396C (portfolio_template_id), INDEX IDX_BB45E5729445617E (apc_niveau_id), PRIMARY KEY (portfolio_template_id, apc_niveau_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE template_section_apc_niveau (template_section_id INT NOT NULL, apc_niveau_id INT NOT NULL, INDEX IDX_B4C30FAAFAE8A6B5 (template_section_id), INDEX IDX_B4C30FAA9445617E (apc_niveau_id), PRIMARY KEY (template_section_id, apc_niveau_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE page_apc_niveau (page_id INT NOT NULL, apc_niveau_id INT NOT NULL, INDEX IDX_570899BDC4663E4 (page_id), INDEX IDX_570899BD9445617E (apc_niveau_id), PRIMARY KEY (page_id, apc_niveau_id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE portfolio_template ADD CONSTRAINT FK_EBEA451BCCF9E01E FOREIGN KEY (departement_id) REFERENCES departement (id)');
        $this->addSql('ALTER TABLE portfolio_template ADD CONSTRAINT FK_EBEA451B9048A9AB FOREIGN KEY (apc_referentiel_id) REFERENCES apc_referentiel (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE portfolio_template ADD CONSTRAINT FK_EBEA451B543EC5F0 FOREIGN KEY (annee_id) REFERENCES annee (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE portfolio_template ADD CONSTRAINT FK_EBEA451B3E102C73 FOREIGN KEY (apc_parcours_id) REFERENCES apc_parcours (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE portfolio_template ADD CONSTRAINT FK_EBEA451BE455FCC0 FOREIGN KEY (enseignant_id) REFERENCES enseignant (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE template_section ADD CONSTRAINT FK_7ECB01D6BBE3396C FOREIGN KEY (portfolio_template_id) REFERENCES portfolio_template (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE portfolio_template_apc_niveau ADD CONSTRAINT FK_BB45E572BBE3396C FOREIGN KEY (portfolio_template_id) REFERENCES portfolio_template (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE portfolio_template_apc_niveau ADD CONSTRAINT FK_BB45E5729445617E FOREIGN KEY (apc_niveau_id) REFERENCES apc_niveau (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE template_section_apc_niveau ADD CONSTRAINT FK_B4C30FAAFAE8A6B5 FOREIGN KEY (template_section_id) REFERENCES template_section (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_section_apc_niveau ADD CONSTRAINT FK_B4C30FAA9445617E FOREIGN KEY (apc_niveau_id) REFERENCES apc_niveau (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE page_apc_niveau ADD CONSTRAINT FK_570899BDC4663E4 FOREIGN KEY (page_id) REFERENCES page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_apc_niveau ADD CONSTRAINT FK_570899BD9445617E FOREIGN KEY (apc_niveau_id) REFERENCES apc_niveau (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE portfolio ADD portfolio_template_id INT DEFAULT NULL, ADD apc_referentiel_id INT DEFAULT NULL, ADD is_guided TINYINT DEFAULT 0 NOT NULL, ADD snapshot_template_nom VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_A9ED1062BBE3396C ON portfolio (portfolio_template_id)');
        $this->addSql('CREATE INDEX IDX_A9ED10629048A9AB ON portfolio (apc_referentiel_id)');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062BBE3396C FOREIGN KEY (portfolio_template_id) REFERENCES portfolio_template (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED10629048A9AB FOREIGN KEY (apc_referentiel_id) REFERENCES apc_referentiel (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB62022CE5C94');
        $this->addSql('DROP INDEX IDX_140AB62022CE5C94 ON page');
        $this->addSql('ALTER TABLE page DROP templates_id, CHANGE description description LONGTEXT DEFAULT NULL, ADD template_section_id INT DEFAULT NULL, ADD consigne LONGTEXT DEFAULT NULL, ADD question_reflexive_prompt LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_140AB620FAE8A6B5 ON page (template_section_id)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB620FAE8A6B5 FOREIGN KEY (template_section_id) REFERENCES template_section (id) ON DELETE SET NULL');

        $this->addSql('DROP TABLE IF EXISTS templates');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE templates (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB620FAE8A6B5');
        $this->addSql('DROP INDEX IDX_140AB620FAE8A6B5 ON page');
        $this->addSql('ALTER TABLE page ADD templates_id INT DEFAULT NULL, CHANGE description description LONGTEXT NOT NULL, DROP template_section_id, DROP consigne, DROP question_reflexive_prompt');
        $this->addSql('CREATE INDEX IDX_140AB62022CE5C94 ON page (templates_id)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB62022CE5C94 FOREIGN KEY (templates_id) REFERENCES templates (id)');

        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED1062BBE3396C');
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY FK_A9ED10629048A9AB');
        $this->addSql('DROP INDEX IDX_A9ED1062BBE3396C ON portfolio');
        $this->addSql('DROP INDEX IDX_A9ED10629048A9AB ON portfolio');
        $this->addSql('ALTER TABLE portfolio DROP portfolio_template_id, DROP apc_referentiel_id, DROP is_guided, DROP snapshot_template_nom');

        $this->addSql('DROP TABLE page_apc_niveau');
        $this->addSql('DROP TABLE template_section_apc_niveau');
        $this->addSql('DROP TABLE portfolio_template_apc_niveau');
        $this->addSql('DROP TABLE template_section');
        $this->addSql('DROP TABLE portfolio_template');
    }
}
