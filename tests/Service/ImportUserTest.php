<?php

namespace App\Tests\Service;

use App\Entity\Departement;
use App\Entity\Users;
use App\Enum\DepartementRoleEnum;
use App\Exception\ImportErrorType;
use App\Exception\ImportException;
use App\Service\ImportUser;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ImportUserTest extends TestCase
{
    private string $csv;
    private EntityManagerInterface $em;
    private EntityRepository $deptRepository;
    private ImportUser $service;

    protected function setUp(): void
    {
        $this->csv = tempnam(sys_get_temp_dir(), 'import_');

        $userRepository = $this->createStub(EntityRepository::class);
        $this->deptRepository = $this->createStub(EntityRepository::class);
        $connection = $this->createStub(Connection::class);
        $connection->method('isTransactionActive')->willReturn(true);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('getRepository')->willReturnMap([
            [Users::class, $userRepository],
            [Departement::class, $this->deptRepository],
        ]);
        $this->em->method('getConnection')->willReturn($connection);

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hash');
        $this->service = new ImportUser($this->em, $hasher);
    }

    protected function tearDown(): void
    {
        @unlink($this->csv);
    }

    private function erreur(string $contenu): ImportErrorType
    {
        file_put_contents($this->csv, $contenu);
        try {
            $this->service->importerCsv($this->csv);
        } catch (ImportException $e) {
            $this->assertSame([], $this->service->getComptesCrees());

            return $e->getType();
        }
        $this->fail('exception attendue');
    }

    public function testImportCreeLesComptes(): void
    {
        file_put_contents($this->csv, "nom;prenom;mail\nDupont;Jean;jean@etu.univ-orleans.fr\nDupont;Jean;j2@univ-orleans.fr\n\n");
        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('commit');

        $this->service->importerCsv($this->csv);
        $comptes = $this->service->getComptesCrees();

        $this->assertSame(['dupont.jean', 'dupont.jean2'], array_column($comptes, 'username'));
        $this->assertSame(['nom', 'prenom', 'username', 'password'], array_keys($comptes[0]));
        $this->assertCount(2, $this->service->getLignesComptesCrees());
    }

    public function testCallbacksDeProgression(): void
    {
        file_put_contents($this->csv, "nom;prenom;mail\nA;B;a@x.fr\nC;D;c@x.fr\n");
        $total = 0;
        $avances = 0;

        $this->service->importerCsv($this->csv, function (int $n) use (&$total) { $total = $n; }, function () use (&$avances) { ++$avances; });

        $this->assertSame(2, $total);
        $this->assertSame(2, $avances);
    }

    public function testRoleExplicite(): void
    {
        file_put_contents($this->csv, "\xEF\xBB\xBFnom,prenom,mail,role\nA,B,a@x.fr,etudiant\nC,D,c@x.fr,prof\n");

        $this->service->importerCsv($this->csv);

        $this->assertCount(2, $this->service->getComptesCrees());
    }

    public function testDefaultRoleEtudiantSansColonneRole(): void
    {
        file_put_contents($this->csv, "nom;prenom;mail\nDupont;Jean;jean@univ-reims.fr\n");
        $persisted = null;
        $this->em->expects($this->once())->method('persist')->willReturnCallback(function (Users $user) use (&$persisted): void {
            $persisted = $user;
        });

        $this->service->importerCsv($this->csv, defaultRole: ImportUser::ROLE_ETUDIANT);

        $this->assertInstanceOf(Users::class, $persisted);
        $this->assertContains(ImportUser::ROLE_ETUDIANT, $persisted->getRoles());
        $this->assertNotNull($persisted->getEtudiant());
    }

    public function testDefaultRoleEnseignantSurDomaineEtudiant(): void
    {
        file_put_contents($this->csv, "nom;prenom;mail\nDupont;Jean;jean@etu.univ-orleans.fr\n");
        $persisted = null;
        $this->em->expects($this->once())->method('persist')->willReturnCallback(function (Users $user) use (&$persisted): void {
            $persisted = $user;
        });

        $this->service->importerCsv($this->csv, defaultRole: ImportUser::ROLE_EVALUATEUR);

        $this->assertInstanceOf(Users::class, $persisted);
        $this->assertContains(ImportUser::ROLE_EVALUATEUR, $persisted->getRoles());
        $this->assertNotNull($persisted->getEnseignant());
    }

    public function testDefaultRoleEditeurSurDomaineEtudiant(): void
    {
        file_put_contents($this->csv, "nom;prenom;mail\nDupont;Jean;jean@etu.univ-orleans.fr\n");
        $persisted = null;
        $this->em->expects($this->once())->method('persist')->willReturnCallback(function (Users $user) use (&$persisted): void {
            $persisted = $user;
        });

        $this->service->importerCsv($this->csv, defaultRole: ImportUser::ROLE_EDITEUR);

        $this->assertInstanceOf(Users::class, $persisted);
        $this->assertContains(ImportUser::ROLE_EDITEUR, $persisted->getRoles());
        $this->assertNotNull($persisted->getEnseignant());
    }

    public function testColonneRolePrioritaireSurDefaultRole(): void
    {
        file_put_contents($this->csv, "nom;prenom;mail;role\nDupont;Jean;jean@x.fr;enseignant\n");
        $persisted = null;
        $this->em->expects($this->once())->method('persist')->willReturnCallback(function (Users $user) use (&$persisted): void {
            $persisted = $user;
        });

        $this->service->importerCsv($this->csv, defaultRole: ImportUser::ROLE_ETUDIANT);

        $this->assertInstanceOf(Users::class, $persisted);
        $this->assertContains(ImportUser::ROLE_EVALUATEUR, $persisted->getRoles());
        $this->assertNotNull($persisted->getEnseignant());
    }

    public function testImportEnseignantAvecColonneDepartementEtRoleDepartement(): void
    {
        $departement = (new Departement())->setLibelle('MMI');
        $this->deptRepository->method('findOneBy')->with(['libelle' => 'MMI'])->willReturn($departement);

        file_put_contents($this->csv, "nom;prenom;mail;role;departement;role_departement\nDupont;Jean;jean@x.fr;enseignant;MMI;editeur\n");
        $persisted = null;
        $this->em->expects($this->once())->method('persist')->willReturnCallback(function (Users $user) use (&$persisted): void {
            $persisted = $user;
        });

        $this->service->importerCsv($this->csv);

        $this->assertInstanceOf(Users::class, $persisted);
        $this->assertContains(ImportUser::ROLE_EDITEUR, $persisted->getRoles());
        $enseignant = $persisted->getEnseignant();
        $this->assertNotNull($enseignant);
        $this->assertCount(1, $enseignant->getEnseignantDepartements());
        $ed = $enseignant->getEnseignantDepartements()->first();
        $this->assertSame($departement, $ed->getDepartement());
        $this->assertSame(DepartementRoleEnum::EDITEUR, $ed->getRole());
        $this->assertTrue($ed->isDefaut());
    }

    public function testImportEnseignantAvecDefaultDepartement(): void
    {
        $departement = (new Departement())->setLibelle('INFO');
        file_put_contents($this->csv, "nom;prenom;mail;role\nMartin;Claire;claire@x.fr;enseignant\n");
        $persisted = null;
        $this->em->expects($this->once())->method('persist')->willReturnCallback(function (Users $user) use (&$persisted): void {
            $persisted = $user;
        });

        $this->service->importerCsv($this->csv, defaultDepartement: $departement);

        $this->assertInstanceOf(Users::class, $persisted);
        $this->assertContains(ImportUser::ROLE_EVALUATEUR, $persisted->getRoles());
        $ed = $persisted->getEnseignant()->getEnseignantDepartements()->first();
        $this->assertSame($departement, $ed->getDepartement());
        $this->assertSame(DepartementRoleEnum::EVALUATEUR, $ed->getRole());
        $this->assertTrue($ed->isDefaut());
    }

    public function testImportEnseignantAvecDepartementInconnuLeveException(): void
    {
        $this->deptRepository->method('findOneBy')->willReturn(null);
        $this->deptRepository->method('findAll')->willReturn([]);

        $this->assertSame(
            ImportErrorType::DonneesInvalides,
            $this->erreur("nom;prenom;mail;role;departement\nDupont;Jean;jean@x.fr;enseignant;INCONNU\n")
        );
    }

    public function testImportEnseignantAvecRoleDepartementInvalideLeveException(): void
    {
        $this->assertSame(
            ImportErrorType::DonneesInvalides,
            $this->erreur("nom;prenom;mail;role;role_departement\nDupont;Jean;jean@x.fr;enseignant;superadmin\n")
        );
    }

    public function testDefaultRoleInvalideLeveException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->importerCsv($this->csv, defaultRole: 'ROLE_INCONNU');
    }

    public function testFichierIllisible(): void
    {
        $this->expectExceptionObject(new ImportException(ImportErrorType::FichierIllisible, 'Fichier illisible : /absent.csv'));

        $this->service->importerCsv('/absent.csv');
    }

    public function testColonneManquante(): void
    {
        $this->assertSame(ImportErrorType::ColonneManquante, $this->erreur("nom;prenom\nA;B\n"));
    }

    public function testLigneIncomplete(): void
    {
        $this->assertSame(ImportErrorType::LigneIncomplete, $this->erreur("nom;prenom;mail\nA;;a@x.fr\n"));
    }

    public function testErreurInattendueEstEnveloppee(): void
    {
        file_put_contents($this->csv, "nom;prenom;mail\nA;B;a@x.fr\n");
        $this->em->method('flush')->willThrowException(new \RuntimeException('base'));

        try {
            $this->service->importerCsv($this->csv);
            $this->fail('exception attendue');
        } catch (ImportException $e) {
            $this->assertSame(ImportErrorType::ImportEchoue, $e->getType());
            $this->assertSame('base', $e->getPrevious()->getMessage());
        }
    }
}
