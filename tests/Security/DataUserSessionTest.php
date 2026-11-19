<?php

namespace App\Tests\Security;

use App\Classes\DataUserSession;
use App\Entity\Departement;
use App\Entity\Enseignant;
use App\Entity\Users;
use App\Enum\DepartementRoleEnum;
use App\Repository\AnneeRepository;
use App\Repository\DepartementRepository;
use App\Repository\DiplomeRepository;
use App\Repository\EnseignantDepartementRepository;
use App\Repository\EnseignantRepository;
use App\Repository\EtudiantRepository;
use App\Repository\GroupeRepository;
use App\Repository\NotificationRepository;
use App\Repository\SemestreRepository;
use App\Repository\TypeGroupeRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DataUserSessionTest extends TestCase
{
    private function createDataUserSession(
        ?Users $user,
        ?int $sessionDeptId = null,
        ?Enseignant $enseignant = null,
        array $departements = [],
        ?Departement $foundById = null
    ): DataUserSession {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        if ($user !== null) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);
            $tokenStorage->method('getToken')->willReturn($token);
        }

        $session = new Session(new MockArraySessionStorage());
        if ($sessionDeptId !== null) {
            $session->set('departement', $sessionDeptId);
        }
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $enseignantRepo = $this->createMock(EnseignantRepository::class);
        $enseignantRepo->method('findOneBy')->willReturn($enseignant);

        $etudiantRepo = $this->createMock(EtudiantRepository::class);
        $etudiantRepo->method('findOneBy')->willReturn(null);

        $deptRepo = $this->createMock(DepartementRepository::class);
        $deptRepo->method('find')->willReturn($foundById);
        $deptRepo->method('findDepartementEnseignant')->willReturn($departements);
        $deptRepo->method('findDepartementEnseignantDefaut')->willReturn([]);
        $deptRepo->method('findAll')->willReturn($departements);

        $semestreRepo = $this->createMock(SemestreRepository::class);
        $semestreRepo->method('findByDepartement')->willReturn([]);

        $diplomeRepo = $this->createMock(DiplomeRepository::class);
        $diplomeRepo->method('findByDepartement')->willReturn([]);

        $anneeRepo = $this->createMock(AnneeRepository::class);
        $anneeRepo->method('findByDepartement')->willReturn([]);

        $typeGroupeRepo = $this->createMock(TypeGroupeRepository::class);
        $typeGroupeRepo->method('findByDepartementSemestresActifs')->willReturn([]);

        return new DataUserSession(
            $semestreRepo,
            $anneeRepo,
            $diplomeRepo,
            $enseignantRepo,
            $etudiantRepo,
            $deptRepo,
            $this->createMock(EnseignantDepartementRepository::class),
            $this->createMock(GroupeRepository::class),
            $typeGroupeRepo,
            $tokenStorage,
            $this->createMock(Security::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $requestStack,
            $this->createMock(NotificationRepository::class),
        );
    }

    public function testUnauthenticatedUserDoesNotThrowException(): void
    {
        $dataSession = $this->createDataUserSession(null);

        $this->assertNull($dataSession->getUser());
        $this->assertNull($dataSession->getDepartement());
        $this->assertFalse($dataSession->isEditeur());
        $this->assertFalse($dataSession->isEvaluateur());
    }

    public function testPureAdminUserHasFullPermissions(): void
    {
        $dept = (new Departement())->setId(1)->setLibelle('INFO');
        $admin = (new Users())->setUsername('admin')->setRoles(['ROLE_ADMIN']);

        $dataSession = $this->createDataUserSession($admin, null, null, [$dept]);

        $this->assertSame($dept, $dataSession->getDepartement());
        $this->assertTrue($dataSession->isEditeur());
        $this->assertTrue($dataSession->isEvaluateur());
        $this->assertSame('Administrateur', $dataSession->getCurrentRoleLibelle());
    }

    public function testMultiDepartmentTeacherRoleChangesWithActiveDepartment(): void
    {
        $deptInfo = (new Departement())->setId(1)->setLibelle('INFO');
        $deptGea = (new Departement())->setId(2)->setLibelle('GEA');

        $enseignant = (new Enseignant())->setUsername('prof_multi');
        $enseignant->addDepartement($deptInfo, DepartementRoleEnum::EDITEUR, true);
        $enseignant->addDepartement($deptGea, DepartementRoleEnum::EVALUATEUR, false);

        $user = (new Users())->setUsername('prof_multi')->setRoles(['ROLE_EDITEUR'])->setEnseignant($enseignant);

        // Session sur INFO -> Éditeur
        $sessionInfo = $this->createDataUserSession($user, 1, $enseignant, [$deptInfo, $deptGea], $deptInfo);
        $this->assertSame($deptInfo, $sessionInfo->getDepartement());
        $this->assertSame(DepartementRoleEnum::EDITEUR, $sessionInfo->getCurrentRole());
        $this->assertSame('Éditeur', $sessionInfo->getCurrentRoleLibelle());
        $this->assertTrue($sessionInfo->isEditeur());
        $this->assertTrue($sessionInfo->isEvaluateur());

        // Session sur GEA -> Évaluateur uniquement
        $sessionGea = $this->createDataUserSession($user, 2, $enseignant, [$deptInfo, $deptGea], $deptGea);
        $this->assertSame($deptGea, $sessionGea->getDepartement());
        $this->assertSame(DepartementRoleEnum::EVALUATEUR, $sessionGea->getCurrentRole());
        $this->assertSame('Évaluateur', $sessionGea->getCurrentRoleLibelle());
        $this->assertFalse($sessionGea->isEditeur());
        $this->assertTrue($sessionGea->isEvaluateur());
    }

    public function testSessionDepartmentOutsideTeacherAssignmentsIsRejected(): void
    {
        $deptInfo = (new Departement())->setId(1)->setLibelle('INFO');
        $deptChimie = (new Departement())->setId(3)->setLibelle('CHIMIE');

        $enseignant = (new Enseignant())->setUsername('prof_info');
        $enseignant->addDepartement($deptInfo, DepartementRoleEnum::EVALUATEUR, true);

        $user = (new Users())->setUsername('prof_info')->setRoles(['ROLE_EVALUATEUR'])->setEnseignant($enseignant);

        // Tentative d'injection de CHIMIE (id 3) en session -> ignorée, repli sur INFO
        $dataSession = $this->createDataUserSession($user, 3, $enseignant, [$deptInfo], $deptChimie);
        $this->assertSame($deptInfo, $dataSession->getDepartement());
    }
}
