<?php

namespace App\Tests\Service;

use App\Exception\ImportErrorType;
use App\Exception\ImportException;
use App\Service\ExportIdentifiants;
use PHPUnit\Framework\TestCase;

class ExportIdentifiantsTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir() . '/export_test_' . uniqid();
        mkdir($this->dossier);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dossier . '/*'));
        rmdir($this->dossier);
    }

    private function compte(array $override = []): array
    {
        return $override + ['nom' => 'Dupont', 'prenom' => 'Jean', 'username' => 'dupont.jean', 'password' => 'abc123'];
    }

    private function erreur(array $comptes, ?string $dossier = null): ImportErrorType
    {
        try {
            (new ExportIdentifiants($dossier ?? $this->dossier))->exporter($comptes);
        } catch (ImportException $e) {
            return $e->getType();
        }
        $this->fail('exception attendue');
    }

    public function testCsvContientEnteteEtLignes(): void
    {
        $response = (new ExportIdentifiants($this->dossier))->exporter([$this->compte()]);
        $lignes = file($response->getFile()->getPathname(), FILE_IGNORE_NEW_LINES);

        $this->assertSame(['nom,prenom,username,password', 'Dupont,Jean,dupont.jean,abc123'], $lignes);
        $this->assertStringContainsString('comptes.csv', $response->headers->get('Content-Disposition'));
    }

    public function testFichierSupprimeApresEnvoi(): void
    {
        $response = (new ExportIdentifiants($this->dossier))->exporter([$this->compte()]);
        $path = $response->getFile()->getPathname();
        $this->assertFileExists($path);

        ob_start();
        $response->sendContent();
        ob_end_clean();

        $this->assertFileDoesNotExist($path);
    }

    public function testListeVide(): void
    {
        $this->assertSame(ImportErrorType::AucunCompte, $this->erreur([]));
    }

    public function testDonneesInvalidesNeLaissentAucunFichier(): void
    {
        $this->assertSame(ImportErrorType::DonneesInvalides, $this->erreur([$this->compte(['password' => ''])]));
        $this->assertSame(ImportErrorType::DonneesInvalides, $this->erreur([$this->compte(['nom' => null])]));
        $this->assertSame([], glob($this->dossier . '/*'));
    }

    public function testDossierInaccessible(): void
    {
        $this->assertSame(ImportErrorType::EcritureImpossible, $this->erreur([$this->compte()], $this->dossier . '/absent'));
    }

    public function testDossierTempParDefaut(): void
    {
        $response = (new ExportIdentifiants())->exporter([$this->compte()]);
        $this->assertFileExists($response->getFile()->getPathname());
        unlink($response->getFile()->getPathname());
    }

    public function testExporterVersEcritLeCsv(): void
    {
        $path = $this->dossier . '/sortie.csv';

        (new ExportIdentifiants())->exporterVers($path, [$this->compte()]);

        $this->assertSame("nom,prenom,username,password\nDupont,Jean,dupont.jean,abc123\n", file_get_contents($path));
        $this->assertSame('0600', substr(sprintf('%o', fileperms($path)), -4));
    }

    public function testExporterVersDossierInaccessible(): void
    {
        try {
            (new ExportIdentifiants())->exporterVers('/inexistant/sortie.csv', [$this->compte()]);
            $this->fail('exception attendue');
        } catch (ImportException $e) {
            $this->assertSame(ImportErrorType::EcritureImpossible, $e->getType());
        }
    }
}
