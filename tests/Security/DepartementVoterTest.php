<?php

namespace App\Tests\Security;

use App\Classes\DataUserSession;
use App\Entity\Annee;
use App\Entity\ApcReferentiel;
use App\Entity\Bibliotheque;
use App\Entity\Departement;
use App\Entity\Diplome;
use App\Entity\Enseignant;
use App\Entity\Etudiant;
use App\Entity\Portfolio;
use App\Entity\PortfolioTemplate;
use App\Entity\Semestre;
use App\Entity\Trace;
use App\Entity\Users;
use App\Enum\DepartementRoleEnum;
use App\Security\Voter\DepartementVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DepartementVoterTest extends TestCase
{
    private DataUserSession $dataUserSession;
    private DepartementVoter $voter;

    private Departement $deptInfo;
    private Departement $deptGea;
    private Departement $deptChimie;

    protected function setUp(): void
    {
        $this->dataUserSession = $this->createMock(DataUserSession::class);
        $this->voter = new DepartementVoter($this->dataUserSession);

        $this->deptInfo = (new Departement())->setId(1)->setLibelle('INFO');
        $this->deptGea = (new Departement())->setId(2)->setLibelle('GEA');
        $this->deptChimie = (new Departement())->setId(3)->setLibelle('CHIMIE');
    }

    private function createToken(?Users $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testAdminHasFullAccessToAllDepartments(): void
    {
        $admin = (new Users())->setRoles(['ROLE_ADMIN']);
        $token = $this->createToken($admin);

        foreach ([$this->deptInfo, $this->deptGea, $this->deptChimie] as $dept) {
            $this->assertSame(
                VoterInterface::ACCESS_GRANTED,
                $this->voter->vote($token, $dept, [DepartementVoter::VIEW])
            );
            $this->assertSame(
                VoterInterface::ACCESS_GRANTED,
                $this->voter->vote($token, $dept, [DepartementVoter::EVALUATE])
            );
            $this->assertSame(
                VoterInterface::ACCESS_GRANTED,
                $this->voter->vote($token, $dept, [DepartementVoter::EDIT])
            );
        }
    }

    public function testTeacherWithMultiDepartmentsSpecificRoles(): void
    {
        $enseignant = new Enseignant();
        $enseignant->addDepartement($this->deptInfo, DepartementRoleEnum::EDITEUR);
        $enseignant->addDepartement($this->deptGea, DepartementRoleEnum::EVALUATEUR);

        $user = (new Users())->setRoles(['ROLE_EDITEUR'])->setEnseignant($enseignant);
        $token = $this->createToken($user);

        // INFO (Éditeur) -> VIEW, EVALUATE, EDIT autorisés
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $this->deptInfo, [DepartementVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $this->deptInfo, [DepartementVoter::EVALUATE]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $this->deptInfo, [DepartementVoter::EDIT]));

        // GEA (Évaluateur) -> VIEW et EVALUATE autorisés, EDIT refusé
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $this->deptGea, [DepartementVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $this->deptGea, [DepartementVoter::EVALUATE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $this->deptGea, [DepartementVoter::EDIT]));

        // CHIMIE (Non rattaché) -> tout refusé (cloisonnement)
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $this->deptChimie, [DepartementVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $this->deptChimie, [DepartementVoter::EVALUATE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $this->deptChimie, [DepartementVoter::EDIT]));
    }

    public function testStudentAccessRestrictions(): void
    {
        $diplome = new Diplome();
        $diplome->setDepartement($this->deptInfo);
        $annee = (new Annee())->setDiplome($diplome);
        $semestre = (new Semestre())->setAnnee($annee);

        $etudiantA = new Etudiant();
        $etudiantA->setSemestre($semestre);
        $etudiantB = new Etudiant();
        $etudiantB->setSemestre($semestre);

        $portfolioA = (new Portfolio())->setEtudiant($etudiantA);
        $portfolioB = (new Portfolio())->setEtudiant($etudiantB);

        $userA = (new Users())->setRoles(['ROLE_ETUDIANT'])->setEtudiant($etudiantA);
        $tokenA = $this->createToken($userA);

        // Son département : VIEW uniquement
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($tokenA, $this->deptInfo, [DepartementVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($tokenA, $this->deptInfo, [DepartementVoter::EVALUATE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($tokenA, $this->deptInfo, [DepartementVoter::EDIT]));

        // Autre département : refusé
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($tokenA, $this->deptGea, [DepartementVoter::VIEW]));

        // Son propre portfolio : autorisé, portfolio d'un autre étudiant : refusé
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($tokenA, $portfolioA, [DepartementVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($tokenA, $portfolioB, [DepartementVoter::VIEW]));
    }

    public function testSubjectResolutionFromPortfolioTraceAndReferentiel(): void
    {
        $diplome = new Diplome();
        $diplome->setDepartement($this->deptGea);
        $annee = (new Annee())->setDiplome($diplome);
        $semestre = (new Semestre())->setAnnee($annee);
        $etudiant = new Etudiant();
        $etudiant->setSemestre($semestre);

        $portfolio = (new Portfolio())->setEtudiant($etudiant);
        $biblio = (new Bibliotheque())->setEtudiant($etudiant);
        $trace = (new Trace())->setBibliotheque($biblio);
        $referentiel = (new ApcReferentiel())->setDepartement($this->deptInfo);

        $enseignant = new Enseignant();
        $enseignant->addDepartement($this->deptInfo, DepartementRoleEnum::EDITEUR);
        $enseignant->addDepartement($this->deptGea, DepartementRoleEnum::EVALUATEUR);
        $teacherUser = (new Users())->setRoles(['ROLE_EDITEUR'])->setEnseignant($enseignant);
        $token = $this->createToken($teacherUser);

        // Portfolio et Trace en GEA -> EVALUATE autorisé, EDIT refusé
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $portfolio, [DepartementVoter::EVALUATE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $portfolio, [DepartementVoter::EDIT]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $trace, [DepartementVoter::EVALUATE]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $trace, [DepartementVoter::EDIT]));

        // Référentiel en INFO -> EDIT autorisé
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $referentiel, [DepartementVoter::EDIT]));
    }

    public function testSubjectNullResolvesActiveSessionDepartement(): void
    {
        $this->dataUserSession->method('getDepartement')->willReturn($this->deptInfo);

        $enseignant = new Enseignant();
        $enseignant->addDepartement($this->deptInfo, DepartementRoleEnum::EDITEUR);
        $teacherUser = (new Users())->setRoles(['ROLE_EDITEUR'])->setEnseignant($enseignant);

        $token = $this->createToken($teacherUser);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, null, [DepartementVoter::EDIT]));
    }

    public function testAnonymousUserIsDeniedAndUnsupportedAttributeAbstains(): void
    {
        $anonToken = $this->createToken(null);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($anonToken, $this->deptInfo, [DepartementVoter::VIEW]));

        $userToken = $this->createToken(new Users());
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($userToken, $this->deptInfo, ['OTHER_ATTR']));
    }

    public function testPortfolioTemplateDepartmentAccess(): void
    {
        $templateInfo = (new PortfolioTemplate())
            ->setNom('Modèle INFO')
            ->setDepartement($this->deptInfo);
        $templateGea = (new PortfolioTemplate())
            ->setNom('Modèle GEA')
            ->setDepartement($this->deptGea);
        $templateChimie = (new PortfolioTemplate())
            ->setNom('Modèle CHIMIE')
            ->setDepartement($this->deptChimie);

        // ROLE_ADMIN -> accès complet sur tous les modèles
        $admin = (new Users())->setRoles(['ROLE_ADMIN']);
        $adminToken = $this->createToken($admin);
        foreach ([$templateInfo, $templateGea, $templateChimie] as $tpl) {
            $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($adminToken, $tpl, [DepartementVoter::VIEW]));
            $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($adminToken, $tpl, [DepartementVoter::EDIT]));
        }

        // Enseignant EDITEUR sur INFO, EVALUATEUR sur GEA, non rattaché à CHIMIE
        $enseignant = new Enseignant();
        $enseignant->addDepartement($this->deptInfo, DepartementRoleEnum::EDITEUR);
        $enseignant->addDepartement($this->deptGea, DepartementRoleEnum::EVALUATEUR);
        $teacherUser = (new Users())->setRoles(['ROLE_EDITEUR'])->setEnseignant($enseignant);
        $teacherToken = $this->createToken($teacherUser);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($teacherToken, $templateInfo, [DepartementVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($teacherToken, $templateInfo, [DepartementVoter::EDIT]));

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($teacherToken, $templateGea, [DepartementVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($teacherToken, $templateGea, [DepartementVoter::EDIT]));

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($teacherToken, $templateChimie, [DepartementVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($teacherToken, $templateChimie, [DepartementVoter::EDIT]));

        // Étudiant de INFO -> VIEW sur INFO, refusé sur EDIT et sur GEA
        $diplome = new Diplome();
        $diplome->setDepartement($this->deptInfo);
        $annee = (new Annee())->setDiplome($diplome);
        $semestre = (new Semestre())->setAnnee($annee);
        $etudiant = new Etudiant();
        $etudiant->setSemestre($semestre);
        $studentUser = (new Users())->setRoles(['ROLE_ETUDIANT'])->setEtudiant($etudiant);
        $studentToken = $this->createToken($studentUser);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($studentToken, $templateInfo, [DepartementVoter::VIEW]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($studentToken, $templateInfo, [DepartementVoter::EDIT]));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($studentToken, $templateGea, [DepartementVoter::VIEW]));
    }
}
