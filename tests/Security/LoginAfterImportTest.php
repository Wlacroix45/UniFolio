<?php

namespace App\Tests\Security;

use App\Entity\Departement;
use App\Entity\Users;
use App\Enum\DepartementRoleEnum;
use App\Repository\DepartementRepository;
use App\Repository\EnseignantRepository;
use App\Repository\EtudiantRepository;
use App\Security\LoginAuthenticator;
use App\Service\ImportUser;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginAfterImportTest extends TestCase
{
    private string $csv;

    protected function setUp(): void
    {
        $this->csv = tempnam(sys_get_temp_dir(), 'import_login_');
    }

    protected function tearDown(): void
    {
        @unlink($this->csv);
    }

    public function testEnseignantImporteAvecDepartementSeConnecteAuDashboard(): void
    {
        $departement = new Departement();
        (new \ReflectionProperty(Departement::class, 'id'))->setValue($departement, 7);
        $departement->setLibelle('MMI');

        $userRepo = $this->createStub(EntityRepository::class);
        $deptEntityRepo = $this->createStub(EntityRepository::class);
        $deptEntityRepo->method('findOneBy')->with(['libelle' => 'MMI'])->willReturn($departement);

        $connection = $this->createStub(Connection::class);
        $connection->method('isTransactionActive')->willReturn(true);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [Users::class, $userRepo],
            [Departement::class, $deptEntityRepo],
        ]);
        $em->method('getConnection')->willReturn($connection);

        $persistedUser = null;
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persistedUser): void {
            if ($entity instanceof Users) {
                $persistedUser = $entity;
            }
        });

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hash');

        file_put_contents($this->csv, "nom;prenom;mail;role;departement;role_departement\nMartin;Claire;claire@univ.fr;enseignant;MMI;editeur\n");
        (new ImportUser($em, $hasher))->importerCsv($this->csv);

        $this->assertInstanceOf(Users::class, $persistedUser);
        $this->assertContains(ImportUser::ROLE_EDITEUR, $persistedUser->getRoles());
        $enseignant = $persistedUser->getEnseignant();
        $this->assertNotNull($enseignant);
        $this->assertSame(DepartementRoleEnum::EDITEUR, $enseignant->getRoleForDepartement($departement));

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $deptRepo = $this->createStub(DepartementRepository::class);
        $deptRepo->method('findDepartementEnseignant')->with($enseignant)->willReturn([$departement]);

        $etudiantRepo = $this->createStub(EtudiantRepository::class);
        $enseignantRepo = $this->createStub(EnseignantRepository::class);
        $enseignantRepo->method('findOneBy')->with(['username' => $persistedUser->getUsername()])->willReturn($enseignant);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('app_dashboard')
            ->willReturn('/dashboard');

        $authenticator = new LoginAuthenticator(
            $deptRepo,
            $requestStack,
            $urlGenerator,
            $etudiantRepo,
            $enseignantRepo,
            $this->createStub(RouterInterface::class),
        );

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($persistedUser);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/dashboard', $response->getTargetUrl());
        $this->assertSame(7, $session->get('departement'));
    }

    public function testEnseignantImporteSansDepartementEstBloqueALaConnexion(): void
    {
        $userRepo = $this->createStub(EntityRepository::class);
        $connection = $this->createStub(Connection::class);
        $connection->method('isTransactionActive')->willReturn(true);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [Users::class, $userRepo],
        ]);
        $em->method('getConnection')->willReturn($connection);

        $persistedUser = null;
        $em->method('persist')->willReturnCallback(function (object $entity) use (&$persistedUser): void {
            if ($entity instanceof Users) {
                $persistedUser = $entity;
            }
        });

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hash');

        file_put_contents($this->csv, "nom;prenom;mail;role\nMartin;Claire;claire@univ.fr;enseignant\n");
        (new ImportUser($em, $hasher))->importerCsv($this->csv);

        $this->assertInstanceOf(Users::class, $persistedUser);
        $enseignant = $persistedUser->getEnseignant();
        $this->assertNotNull($enseignant);

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $deptRepo = $this->createStub(DepartementRepository::class);
        $deptRepo->method('findDepartementEnseignant')->with($enseignant)->willReturn([]);

        $etudiantRepo = $this->createStub(EtudiantRepository::class);
        $enseignantRepo = $this->createStub(EnseignantRepository::class);
        $enseignantRepo->method('findOneBy')->with(['username' => $persistedUser->getUsername()])->willReturn($enseignant);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('app_login', ['message' => 'pas_departement'])
            ->willReturn('/login?message=pas_departement');

        $authenticator = new LoginAuthenticator(
            $deptRepo,
            $requestStack,
            $urlGenerator,
            $etudiantRepo,
            $enseignantRepo,
            $this->createStub(RouterInterface::class),
        );

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($persistedUser);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login?message=pas_departement', $response->getTargetUrl());
    }
}
