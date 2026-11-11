<?php

namespace App\Tests\Command;

use App\Command\ImportUsersCommand;
use App\Exception\ImportErrorType;
use App\Exception\ImportException;
use App\Service\ExportIdentifiants;
use App\Service\ImportUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ImportUsersCommandTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir() . '/cmd_test_' . uniqid();
        mkdir($this->dossier);
        file_put_contents($this->dossier . '/a.csv', "nom;prenom;mail\nA;B;a@x.fr\n");
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dossier . '/*'));
        rmdir($this->dossier);
    }

    private function tester(ImportUser $import): CommandTester
    {
        return new CommandTester(new ImportUsersCommand($import, new ExportIdentifiants(), $this->dossier));
    }

    private function importAvecComptes(): ImportUser
    {
        $import = $this->createMock(ImportUser::class);
        $import->method('importerCsv')->willReturnCallback(static function (string $path, callable $onStart, callable $onProgress): void {
            $onStart(1);
            $onProgress();
        });
        $import->method('getComptesCrees')->willReturn([
            ['nom' => 'A', 'prenom' => 'B', 'username' => 'a.b', 'password' => 'pw'],
        ]);
        $import->method('getLignesComptesCrees')->willReturn(['a.b : pw']);

        return $import;
    }

    public function testAucunFichier(): void
    {
        $tester = $this->tester($this->createStub(ImportUser::class));

        $this->assertSame(1, $tester->execute(['--path' => $this->dossier . '/absent']));
        $this->assertStringContainsString('Aucun fichier CSV', $tester->getDisplay());
    }

    public function testImportAffichePuisSucces(): void
    {
        $tester = $this->tester($this->importAvecComptes());

        $this->assertSame(0, $tester->execute(['--path' => $this->dossier]));
        $this->assertStringContainsString('a.b : pw', $tester->getDisplay());
    }

    public function testImportAvecExport(): void
    {
        $sortie = $this->dossier . '/identifiants.csv';
        $tester = $this->tester($this->importAvecComptes());

        $this->assertSame(0, $tester->execute(['--path' => $this->dossier . '/a.csv', '--export' => $sortie]));
        $this->assertSame("nom,prenom,username,password\nA,B,a.b,pw\n", file_get_contents($sortie));
    }

    public function testErreurImport(): void
    {
        $import = $this->createStub(ImportUser::class);
        $import->method('importerCsv')->willThrowException(new ImportException(ImportErrorType::ColonneManquante, 'Colonne manquante : mail'));
        $tester = $this->tester($import);

        $this->assertSame(1, $tester->execute(['--path' => $this->dossier]));
        $this->assertStringContainsString('[colonne_manquante]', $tester->getDisplay());
    }

    public function testErreurExport(): void
    {
        $tester = $this->tester($this->importAvecComptes());

        $this->assertSame(1, $tester->execute(['--path' => $this->dossier . '/a.csv', '--export' => '/inexistant/x.csv']));
        $this->assertStringContainsString('[ecriture_impossible]', $tester->getDisplay());
    }
}
