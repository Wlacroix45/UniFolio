<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261126170016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OréBUT : id auto-incrémentés + intranet_id, champs de synchro OréBUT, composantes essentielles, situations pro, SAÉ, ressources, liens trace -> AC/SAÉ';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE apc_apprentissage_critique DROP FOREIGN KEY FK_A99B947AAAC4B70E');
        $this->addSql('ALTER TABLE apc_niveau DROP FOREIGN KEY FK_5CE8A823A660B158');
        $this->addSql('ALTER TABLE apc_niveau_apc_parcours DROP FOREIGN KEY FK_496327F53E102C73');
        $this->addSql('ALTER TABLE apc_niveau_apc_parcours DROP FOREIGN KEY FK_496327F59445617E');
        $this->addSql('ALTER TABLE apc_parcours DROP FOREIGN KEY FK_5DA884A59048A9AB');
        $this->addSql('ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F805DB139');
        $this->addSql('ALTER TABLE diplome DROP FOREIGN KEY FK_EB4C4D4E3E102C73');
        $this->addSql('ALTER TABLE groupe DROP FOREIGN KEY FK_4B98C213E102C73');
        $this->addSql('ALTER TABLE validation DROP FOREIGN KEY FK_16AC5B6E9445617E');

        $this->addSql('ALTER TABLE apc_apprentissage_critique ADD intranet_id INT DEFAULT NULL, ADD orebut_id INT DEFAULT NULL, ADD orebut_snapshot JSON DEFAULT NULL, ADD orebut_supprime_le DATETIME DEFAULT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE apc_niveau ADD intranet_id INT DEFAULT NULL, ADD orebut_id INT DEFAULT NULL, ADD orebut_snapshot JSON DEFAULT NULL, ADD orebut_supprime_le DATETIME DEFAULT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE apc_parcours ADD intranet_id INT DEFAULT NULL, ADD orebut_id INT DEFAULT NULL, ADD orebut_snapshot JSON DEFAULT NULL, ADD orebut_supprime_le DATETIME DEFAULT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE apc_referentiel ADD intranet_id INT DEFAULT NULL, ADD code VARCHAR(20) DEFAULT NULL, ADD date_version_orebut DATETIME DEFAULT NULL, ADD orebut_id INT DEFAULT NULL, ADD orebut_snapshot JSON DEFAULT NULL, ADD orebut_supprime_le DATETIME DEFAULT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE competence ADD intranet_id INT DEFAULT NULL, ADD numero INT DEFAULT NULL, ADD orebut_id INT DEFAULT NULL, ADD orebut_snapshot JSON DEFAULT NULL, ADD orebut_supprime_le DATETIME DEFAULT NULL, DROP code, CHANGE id id INT AUTO_INCREMENT NOT NULL');

        $this->addSql('UPDATE apc_referentiel SET intranet_id = id');
        $this->addSql('UPDATE apc_parcours SET intranet_id = id');
        $this->addSql('UPDATE competence SET intranet_id = id');
        $this->addSql('UPDATE apc_niveau SET intranet_id = id');
        $this->addSql('UPDATE apc_apprentissage_critique SET intranet_id = id');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_A99B947AFFBAD309 ON apc_apprentissage_critique (intranet_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A99B947A5B7B4573 ON apc_apprentissage_critique (orebut_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_ac_niveau_code ON apc_apprentissage_critique (niveaux_id, code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5CE8A823FFBAD309 ON apc_niveau (intranet_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5CE8A8235B7B4573 ON apc_niveau (orebut_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_niveau_competence_ordre ON apc_niveau (competences_id, ordre)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5DA884A5FFBAD309 ON apc_parcours (intranet_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5DA884A55B7B4573 ON apc_parcours (orebut_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_parcours_referentiel_code ON apc_parcours (apc_referentiel_id, code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E744CC81FFBAD309 ON apc_referentiel (intranet_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E744CC815B7B4573 ON apc_referentiel (orebut_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_referentiel_code_annee ON apc_referentiel (code, annee_publication)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_94D4687FFFBAD309 ON competence (intranet_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_94D4687F5B7B4573 ON competence (orebut_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_competence_referentiel_numero ON competence (referentiel_id, numero)');

        $this->addSql('ALTER TABLE apc_apprentissage_critique ADD CONSTRAINT FK_A99B947AAAC4B70E FOREIGN KEY (niveaux_id) REFERENCES apc_niveau (id)');
        $this->addSql('ALTER TABLE apc_niveau ADD CONSTRAINT FK_5CE8A823A660B158 FOREIGN KEY (competences_id) REFERENCES competence (id)');
        $this->addSql('ALTER TABLE apc_niveau_apc_parcours ADD CONSTRAINT FK_496327F53E102C73 FOREIGN KEY (apc_parcours_id) REFERENCES apc_parcours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE apc_niveau_apc_parcours ADD CONSTRAINT FK_496327F59445617E FOREIGN KEY (apc_niveau_id) REFERENCES apc_niveau (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE apc_parcours ADD CONSTRAINT FK_5DA884A59048A9AB FOREIGN KEY (apc_referentiel_id) REFERENCES apc_referentiel (id)');
        $this->addSql('ALTER TABLE competence ADD CONSTRAINT FK_94D4687F805DB139 FOREIGN KEY (referentiel_id) REFERENCES apc_referentiel (id)');
        $this->addSql('ALTER TABLE diplome ADD CONSTRAINT FK_EB4C4D4E3E102C73 FOREIGN KEY (apc_parcours_id) REFERENCES apc_parcours (id)');
        $this->addSql('ALTER TABLE groupe ADD CONSTRAINT FK_4B98C213E102C73 FOREIGN KEY (apc_parcours_id) REFERENCES apc_parcours (id)');
        $this->addSql('ALTER TABLE validation ADD CONSTRAINT FK_16AC5B6E9445617E FOREIGN KEY (apc_niveau_id) REFERENCES apc_niveau (id)');

        $this->addSql('CREATE TABLE apc_composante_essentielle (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) DEFAULT NULL, ordre INT DEFAULT NULL, code VARCHAR(20) DEFAULT NULL, orebut_id INT DEFAULT NULL, orebut_snapshot JSON DEFAULT NULL, orebut_supprime_le DATETIME DEFAULT NULL, competence_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_AC23ECFF5B7B4573 (orebut_id), UNIQUE INDEX uniq_composante_competence_ordre (competence_id, ordre), INDEX IDX_AC23ECFF15761DAB (competence_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE apc_ressource (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) DEFAULT NULL, code_matiere VARCHAR(20) DEFAULT NULL, description LONGTEXT DEFAULT NULL, orebut_id INT DEFAULT NULL, orebut_snapshot JSON DEFAULT NULL, orebut_supprime_le DATETIME DEFAULT NULL, semestre_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_CE4B8BB5B7B4573 (orebut_id), UNIQUE INDEX uniq_ressource_semestre_code_matiere (semestre_id, code_matiere), INDEX IDX_CE4B8BB5577AFDB (semestre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE apc_sae (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) DEFAULT NULL, code_matiere VARCHAR(20) DEFAULT NULL, description LONGTEXT DEFAULT NULL, portfolio TINYINT NOT NULL, orebut_id INT DEFAULT NULL, orebut_snapshot JSON DEFAULT NULL, orebut_supprime_le DATETIME DEFAULT NULL, semestre_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_E1FA9EBF5B7B4573 (orebut_id), UNIQUE INDEX uniq_sae_semestre_code_matiere (semestre_id, code_matiere), INDEX IDX_E1FA9EBF5577AFDB (semestre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE apc_sae_apprentissage_critique (apc_sae_id INT NOT NULL, apc_apprentissage_critique_id INT NOT NULL, INDEX IDX_A2B4508D1AE9C06A (apc_sae_id), INDEX IDX_A2B4508D4DD72DCD (apc_apprentissage_critique_id), PRIMARY KEY (apc_sae_id, apc_apprentissage_critique_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE apc_sae_ressource (apc_sae_id INT NOT NULL, apc_ressource_id INT NOT NULL, INDEX IDX_68D508601AE9C06A (apc_sae_id), INDEX IDX_68D508602431D61C (apc_ressource_id), PRIMARY KEY (apc_sae_id, apc_ressource_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE apc_situation_professionnelle (id INT AUTO_INCREMENT NOT NULL, libelle LONGTEXT DEFAULT NULL, orebut_id INT DEFAULT NULL, orebut_snapshot JSON DEFAULT NULL, orebut_supprime_le DATETIME DEFAULT NULL, competence_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_29E092C35B7B4573 (orebut_id), INDEX IDX_29E092C315761DAB (competence_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE trace_apprentissage_critique (trace_id INT NOT NULL, apc_apprentissage_critique_id INT NOT NULL, INDEX IDX_80AE3FE0BE0D4B70 (trace_id), INDEX IDX_80AE3FE04DD72DCD (apc_apprentissage_critique_id), PRIMARY KEY (trace_id, apc_apprentissage_critique_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE apc_composante_essentielle ADD CONSTRAINT FK_AC23ECFF15761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)');
        $this->addSql('ALTER TABLE apc_ressource ADD CONSTRAINT FK_CE4B8BB5577AFDB FOREIGN KEY (semestre_id) REFERENCES semestre (id)');
        $this->addSql('ALTER TABLE apc_sae ADD CONSTRAINT FK_E1FA9EBF5577AFDB FOREIGN KEY (semestre_id) REFERENCES semestre (id)');
        $this->addSql('ALTER TABLE apc_sae_apprentissage_critique ADD CONSTRAINT FK_A2B4508D1AE9C06A FOREIGN KEY (apc_sae_id) REFERENCES apc_sae (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE apc_sae_apprentissage_critique ADD CONSTRAINT FK_A2B4508D4DD72DCD FOREIGN KEY (apc_apprentissage_critique_id) REFERENCES apc_apprentissage_critique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE apc_sae_ressource ADD CONSTRAINT FK_68D508601AE9C06A FOREIGN KEY (apc_sae_id) REFERENCES apc_sae (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE apc_sae_ressource ADD CONSTRAINT FK_68D508602431D61C FOREIGN KEY (apc_ressource_id) REFERENCES apc_ressource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE apc_situation_professionnelle ADD CONSTRAINT FK_29E092C315761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)');
        $this->addSql('ALTER TABLE trace_apprentissage_critique ADD CONSTRAINT FK_80AE3FE0BE0D4B70 FOREIGN KEY (trace_id) REFERENCES trace (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trace_apprentissage_critique ADD CONSTRAINT FK_80AE3FE04DD72DCD FOREIGN KEY (apc_apprentissage_critique_id) REFERENCES apc_apprentissage_critique (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE departement ADD sigle VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C1765B638776B952 ON departement (sigle)');
        $this->addSql('ALTER TABLE trace ADD sae_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE trace ADD CONSTRAINT FK_315BD5A1DDE9B22 FOREIGN KEY (sae_id) REFERENCES apc_sae (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_315BD5A1DDE9B22 ON trace (sae_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_C1765B638776B952 ON departement');
        $this->addSql('ALTER TABLE departement DROP sigle');
        $this->addSql('ALTER TABLE trace DROP FOREIGN KEY FK_315BD5A1DDE9B22');
        $this->addSql('DROP INDEX IDX_315BD5A1DDE9B22 ON trace');
        $this->addSql('ALTER TABLE trace DROP sae_id');

        $this->addSql('ALTER TABLE apc_composante_essentielle DROP FOREIGN KEY FK_AC23ECFF15761DAB');
        $this->addSql('ALTER TABLE apc_ressource DROP FOREIGN KEY FK_CE4B8BB5577AFDB');
        $this->addSql('ALTER TABLE apc_sae DROP FOREIGN KEY FK_E1FA9EBF5577AFDB');
        $this->addSql('ALTER TABLE apc_sae_apprentissage_critique DROP FOREIGN KEY FK_A2B4508D1AE9C06A');
        $this->addSql('ALTER TABLE apc_sae_apprentissage_critique DROP FOREIGN KEY FK_A2B4508D4DD72DCD');
        $this->addSql('ALTER TABLE apc_sae_ressource DROP FOREIGN KEY FK_68D508601AE9C06A');
        $this->addSql('ALTER TABLE apc_sae_ressource DROP FOREIGN KEY FK_68D508602431D61C');
        $this->addSql('ALTER TABLE apc_situation_professionnelle DROP FOREIGN KEY FK_29E092C315761DAB');
        $this->addSql('ALTER TABLE trace_apprentissage_critique DROP FOREIGN KEY FK_80AE3FE0BE0D4B70');
        $this->addSql('ALTER TABLE trace_apprentissage_critique DROP FOREIGN KEY FK_80AE3FE04DD72DCD');
        $this->addSql('DROP TABLE apc_composante_essentielle');
        $this->addSql('DROP TABLE apc_ressource');
        $this->addSql('DROP TABLE apc_sae');
        $this->addSql('DROP TABLE apc_sae_apprentissage_critique');
        $this->addSql('DROP TABLE apc_sae_ressource');
        $this->addSql('DROP TABLE apc_situation_professionnelle');
        $this->addSql('DROP TABLE trace_apprentissage_critique');

        $this->addSql('ALTER TABLE apc_apprentissage_critique DROP FOREIGN KEY FK_A99B947AAAC4B70E');
        $this->addSql('ALTER TABLE apc_niveau DROP FOREIGN KEY FK_5CE8A823A660B158');
        $this->addSql('ALTER TABLE apc_niveau_apc_parcours DROP FOREIGN KEY FK_496327F53E102C73');
        $this->addSql('ALTER TABLE apc_niveau_apc_parcours DROP FOREIGN KEY FK_496327F59445617E');
        $this->addSql('ALTER TABLE apc_parcours DROP FOREIGN KEY FK_5DA884A59048A9AB');
        $this->addSql('ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F805DB139');
        $this->addSql('ALTER TABLE diplome DROP FOREIGN KEY FK_EB4C4D4E3E102C73');
        $this->addSql('ALTER TABLE groupe DROP FOREIGN KEY FK_4B98C213E102C73');
        $this->addSql('ALTER TABLE validation DROP FOREIGN KEY FK_16AC5B6E9445617E');

        $this->addSql('DROP INDEX UNIQ_A99B947AFFBAD309 ON apc_apprentissage_critique');
        $this->addSql('DROP INDEX UNIQ_A99B947A5B7B4573 ON apc_apprentissage_critique');
        $this->addSql('DROP INDEX uniq_ac_niveau_code ON apc_apprentissage_critique');
        $this->addSql('ALTER TABLE apc_apprentissage_critique DROP intranet_id, DROP orebut_id, DROP orebut_snapshot, DROP orebut_supprime_le, CHANGE id id INT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_5CE8A823FFBAD309 ON apc_niveau');
        $this->addSql('DROP INDEX UNIQ_5CE8A8235B7B4573 ON apc_niveau');
        $this->addSql('DROP INDEX uniq_niveau_competence_ordre ON apc_niveau');
        $this->addSql('ALTER TABLE apc_niveau DROP intranet_id, DROP orebut_id, DROP orebut_snapshot, DROP orebut_supprime_le, CHANGE id id INT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_5DA884A5FFBAD309 ON apc_parcours');
        $this->addSql('DROP INDEX UNIQ_5DA884A55B7B4573 ON apc_parcours');
        $this->addSql('DROP INDEX uniq_parcours_referentiel_code ON apc_parcours');
        $this->addSql('ALTER TABLE apc_parcours DROP intranet_id, DROP orebut_id, DROP orebut_snapshot, DROP orebut_supprime_le, CHANGE id id INT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_E744CC81FFBAD309 ON apc_referentiel');
        $this->addSql('DROP INDEX UNIQ_E744CC815B7B4573 ON apc_referentiel');
        $this->addSql('DROP INDEX uniq_referentiel_code_annee ON apc_referentiel');
        $this->addSql('ALTER TABLE apc_referentiel DROP intranet_id, DROP code, DROP date_version_orebut, DROP orebut_id, DROP orebut_snapshot, DROP orebut_supprime_le, CHANGE id id INT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_94D4687FFFBAD309 ON competence');
        $this->addSql('DROP INDEX UNIQ_94D4687F5B7B4573 ON competence');
        $this->addSql('DROP INDEX uniq_competence_referentiel_numero ON competence');
        $this->addSql('ALTER TABLE competence ADD code VARCHAR(20) DEFAULT NULL, DROP intranet_id, DROP numero, DROP orebut_id, DROP orebut_snapshot, DROP orebut_supprime_le, CHANGE id id INT NOT NULL');

        $this->addSql('ALTER TABLE apc_apprentissage_critique ADD CONSTRAINT FK_A99B947AAAC4B70E FOREIGN KEY (niveaux_id) REFERENCES apc_niveau (id)');
        $this->addSql('ALTER TABLE apc_niveau ADD CONSTRAINT FK_5CE8A823A660B158 FOREIGN KEY (competences_id) REFERENCES competence (id)');
        $this->addSql('ALTER TABLE apc_niveau_apc_parcours ADD CONSTRAINT FK_496327F53E102C73 FOREIGN KEY (apc_parcours_id) REFERENCES apc_parcours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE apc_niveau_apc_parcours ADD CONSTRAINT FK_496327F59445617E FOREIGN KEY (apc_niveau_id) REFERENCES apc_niveau (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE apc_parcours ADD CONSTRAINT FK_5DA884A59048A9AB FOREIGN KEY (apc_referentiel_id) REFERENCES apc_referentiel (id)');
        $this->addSql('ALTER TABLE competence ADD CONSTRAINT FK_94D4687F805DB139 FOREIGN KEY (referentiel_id) REFERENCES apc_referentiel (id)');
        $this->addSql('ALTER TABLE diplome ADD CONSTRAINT FK_EB4C4D4E3E102C73 FOREIGN KEY (apc_parcours_id) REFERENCES apc_parcours (id)');
        $this->addSql('ALTER TABLE groupe ADD CONSTRAINT FK_4B98C213E102C73 FOREIGN KEY (apc_parcours_id) REFERENCES apc_parcours (id)');
        $this->addSql('ALTER TABLE validation ADD CONSTRAINT FK_16AC5B6E9445617E FOREIGN KEY (apc_niveau_id) REFERENCES apc_niveau (id)');
    }
}
