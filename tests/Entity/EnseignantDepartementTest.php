<?php

namespace App\Tests\Entity;

use App\Entity\Departement;
use App\Entity\Enseignant;
use App\Entity\EnseignantDepartement;
use App\Entity\Users;
use App\Enum\DepartementRoleEnum;
use PHPUnit\Framework\TestCase;

class EnseignantDepartementTest extends TestCase
{
    public function testEnseignantDepartementCreationAndRoles(): void
    {
        $enseignant = new Enseignant();
        $dept = (new Departement())->setId(1)->setLibelle('INFO');

        $ed = new EnseignantDepartement($enseignant, $dept, DepartementRoleEnum::EDITEUR);
        $ed->setDefaut(true);

        $this->assertSame($enseignant, $ed->getEnseignant());
        $this->assertSame($dept, $ed->getDepartement());
        $this->assertSame(DepartementRoleEnum::EDITEUR, $ed->getRole());
        $this->assertTrue($ed->isEditeur());
        $this->assertTrue($ed->isEvaluateur());
        $this->assertTrue($ed->isDefaut());
        $this->assertSame(['ROLE_EDITEUR'], $ed->getRoles());

        $ed->setRole(DepartementRoleEnum::EVALUATEUR);
        $this->assertFalse($ed->isEditeur());
        $this->assertTrue($ed->isEvaluateur());
        $this->assertSame(['ROLE_EVALUATEUR'], $ed->getRoles());
    }

    public function testTeacherEditeurInInfoAndEvaluateurInGea(): void
    {
        $deptInfo = (new Departement())->setId(10)->setLibelle('INFO');
        $deptGea = (new Departement())->setId(20)->setLibelle('GEA');
        $deptChimie = (new Departement())->setId(30)->setLibelle('CHIMIE');

        $enseignant = new Enseignant();
        $enseignant->addDepartement($deptInfo, DepartementRoleEnum::EDITEUR, true);
        $enseignant->addDepartement($deptGea, DepartementRoleEnum::EVALUATEUR, false);

        $user = (new Users())->setRoles(['ROLE_EDITEUR'])->setEnseignant($enseignant);

        // Relation ManyToMany
        $this->assertCount(2, $enseignant->getDepartements());
        $this->assertCount(2, $user->getDepartements());

        // INFO : Éditeur (donc éditeur + évaluateur)
        $this->assertSame(DepartementRoleEnum::EDITEUR, $user->getRoleForDepartement($deptInfo));
        $this->assertTrue($user->hasRoleInDepartement($deptInfo, DepartementRoleEnum::EDITEUR));
        $this->assertTrue($user->hasRoleInDepartement($deptInfo, DepartementRoleEnum::EVALUATEUR));

        // GEA : Évaluateur uniquement
        $this->assertSame(DepartementRoleEnum::EVALUATEUR, $user->getRoleForDepartement($deptGea));
        $this->assertFalse($user->hasRoleInDepartement($deptGea, DepartementRoleEnum::EDITEUR));
        $this->assertTrue($user->hasRoleInDepartement($deptGea, DepartementRoleEnum::EVALUATEUR));

        // CHIMIE : non rattaché
        $this->assertNull($user->getRoleForDepartement($deptChimie));
        $this->assertFalse($user->hasRoleInDepartement($deptChimie, DepartementRoleEnum::EVALUATEUR));
        $this->assertFalse($user->hasRoleInDepartement($deptChimie, DepartementRoleEnum::EDITEUR));
    }

    public function testAddDepartementUpdatesExistingInsteadOfDuplicating(): void
    {
        $deptInfo = (new Departement())->setId(10)->setLibelle('INFO');
        $enseignant = new Enseignant();

        $enseignant->addDepartement($deptInfo, DepartementRoleEnum::EVALUATEUR);
        $enseignant->addDepartement($deptInfo, DepartementRoleEnum::EDITEUR, true);

        $this->assertCount(1, $enseignant->getEnseignantDepartements());
        $this->assertSame(DepartementRoleEnum::EDITEUR, $enseignant->getRoleForDepartement($deptInfo));
        $this->assertTrue($enseignant->getEnseignantDepartement($deptInfo)->isDefaut());
    }

    public function testDepartementSideRelationship(): void
    {
        $dept = (new Departement())->setId(1)->setLibelle('INFO');
        $enseignant = (new Enseignant())->setNom('Dupont');

        $dept->addEnseignant($enseignant, DepartementRoleEnum::EDITEUR);

        $this->assertCount(1, $dept->getEnseignants());
        $this->assertTrue($dept->getEnseignants()->contains($enseignant));
        $this->assertSame(DepartementRoleEnum::EDITEUR, $enseignant->getRoleForDepartement($dept));

        $dept->removeEnseignant($enseignant);
        $this->assertCount(0, $dept->getEnseignants());
        $this->assertNull($enseignant->getRoleForDepartement($dept));
    }

    public function testToString(): void
    {
        $dept = (new Departement())->setLibelle('INFO');
        $ed = new EnseignantDepartement(new Enseignant(), $dept, DepartementRoleEnum::EDITEUR);
        $ed->setDefaut(true);

        $this->assertSame('INFO (Éditeur) [Défaut]', (string) $ed);
    }

    public function testEnseignantRolesDelegationToUsers(): void
    {
        $user = (new Users())->setUsername('prof1');
        $enseignant = (new Enseignant())->setUsername('prof1')->setUsers($user);

        $enseignant->setRoles(['ROLE_EDITEUR', 'ROLE_ADMIN']);

        $this->assertContains('ROLE_EDITEUR', $user->getRoles());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_EDITEUR', $enseignant->getRoles());
    }
}
