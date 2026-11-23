<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Etudiant;
use App\Entity\Users;
use App\Exception\ImportErrorType;
use App\Exception\ImportException;
use App\Repository\UsersRepository;
use App\Service\ExportIdentifiants;
use App\Service\ImportUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportUtilisateursControllerTest extends WebTestCase
{
    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        $this->tempFiles = [];

        parent::tearDown();
    }

    private function createUser(array $roles): Users
    {
        $user = (new Users())
            ->setUsername('admin.test')
            ->setEmail('admin@test.fr')
            ->setRoles($roles)
            ->setIsVerified(true);

        (new \ReflectionProperty(Users::class, 'id'))->setValue($user, 1);
        if (in_array('ROLE_ETUDIANT', $roles, true)) {
            $user->setEtudiant((new Etudiant())->setNom('Test')->setPrenom('Etudiant'));
        }

        return $user;
    }

    private function createCsvUpload(string $content, string $filename = 'import.csv'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_upload_');
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, $filename, 'text/csv', null, true);
    }

    private function loginWithUser(KernelBrowser $client, array $roles): void
    {
        $user = $this->createUser($roles);
        $client->disableReboot();

        $repo = $this->createStub(UsersRepository::class);
        $repo->method('find')->willReturn($user);
        static::getContainer()->set(UsersRepository::class, $repo);

        $client->loginUser($user);
    }

    private function loginAsAdmin(KernelBrowser $client): void
    {
        $this->loginWithUser($client, ['ROLE_ADMIN']);
    }

    public function testAccesRefuseSansRoleAdmin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/import-utilisateurs');

        $this->assertResponseRedirects('/login');
    }

    public function testAccesRefusePourEtudiant(): void
    {
        $client = static::createClient();
        $this->loginWithUser($client, ['ROLE_ETUDIANT']);
        $client->request('GET', '/admin/import-utilisateurs');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSelectorTextContains('.error_code', '403');
    }

    public function testDashboardContientLienImportPourToutSite(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('/admin/import-utilisateurs', $client->getResponse()->getContent());
        $this->assertGreaterThan(0, $crawler->filter('a[href="/admin/import-utilisateurs"]')->count());
    }

    public function testPageImportAfficheFormulaireEtDropzone(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/import-utilisateurs');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#main-menu a[href$="/admin/import-utilisateurs"]');
        $this->assertSelectorExists('[data-controller="import-users"]');
        $this->assertSelectorExists('[data-import-users-target="dropzone"]');
        $this->assertSelectorExists('select[name="import_utilisateurs[defaultRole]"]');
        $this->assertSelectorExists('input[type="file"][name="import_utilisateurs[fichier]"]');
        $this->assertGreaterThan(0, $crawler->filter('a[href="/admin/import-utilisateurs/modele"]')->count());
    }

    public function testImportAjaxReussiEtTelechargementParDefaut(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $importUser = $this->createMock(ImportUser::class);
        $importUser->expects($this->once())
            ->method('importerCsv')
            ->with(
                $this->isString(),
                null,
                null,
                ImportUser::ROLE_ETUDIANT
            );
        $importUser->method('getComptesCrees')->willReturn([
            ['nom' => 'Dupont', 'prenom' => 'Jean', 'username' => 'dupont.jean', 'password' => 'secret12'],
            ['nom' => 'Martin', 'prenom' => 'Claire', 'username' => 'martin.claire', 'password' => 'secret34'],
        ]);
        static::getContainer()->set(ImportUser::class, $importUser);

        $crawler = $client->request('GET', '/admin/import-utilisateurs');
        $csrfToken = $crawler->filter('input[name="import_utilisateurs[_token]"]')->attr('value');
        $downloadCsrfToken = $crawler->filter('form[data-import-users-target="downloadForm"] input[name="_csrf_token"]')->attr('value');

        $uploadedFile = $this->createCsvUpload("nom;prenom;mail\nDupont;Jean;jean@x.fr\nMartin;Claire;claire@x.fr\n");

        $client->request(
            'POST',
            '/admin/import-utilisateurs',
            [
                'import_utilisateurs' => [
                    'defaultRole' => ImportUser::ROLE_ETUDIANT,
                    '_token' => $csrfToken,
                ],
            ],
            [
                'import_utilisateurs' => [
                    'fichier' => $uploadedFile,
                ],
            ],
            [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($payload['success']);
        $this->assertSame(2, $payload['count']);
        $this->assertArrayNotHasKey('comptes', $payload);
        $this->assertArrayNotHasKey('defaultFilename', $payload);
        $this->assertNotEmpty($payload['token']);

        // Téléchargement du fichier généré avec le nom par défaut (comptes.csv)
        $client->request('POST', '/admin/import-utilisateurs/telecharger', [
            '_csrf_token' => $downloadCsrfToken,
            'token' => $payload['token'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            'comptes.csv',
            (string) $client->getResponse()->headers->get('Content-Disposition')
        );
        $this->assertSame(
            "nom,prenom,username,password\nDupont,Jean,dupont.jean,secret12\nMartin,Claire,martin.claire,secret34\n",
            $client->getInternalResponse()->getContent()
        );
    }

    public function testImportAjaxGereImportException(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $importUser = $this->createStub(ImportUser::class);
        $importUser->method('importerCsv')
            ->willThrowException(new ImportException(ImportErrorType::ColonneManquante, 'Colonne manquante : mail'));
        static::getContainer()->set(ImportUser::class, $importUser);

        $crawler = $client->request('GET', '/admin/import-utilisateurs');
        $csrfToken = $crawler->filter('input[name="import_utilisateurs[_token]"]')->attr('value');

        $uploadedFile = $this->createCsvUpload("nom;prenom\nDupont;Jean\n");

        $client->request(
            'POST',
            '/admin/import-utilisateurs',
            [
                'import_utilisateurs' => [
                    'defaultRole' => '',
                    '_token' => $csrfToken,
                ],
            ],
            [
                'import_utilisateurs' => [
                    'fichier' => $uploadedFile,
                ],
            ],
            [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($payload['success']);
        $this->assertSame(ImportErrorType::ColonneManquante->value, $payload['errorType']);
        $this->assertSame('Colonne manquante : mail', $payload['message']);
    }

    public function testImportAjaxRejetteCsvSansLigneDeDonnees(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $importUser = $this->createStub(ImportUser::class);
        $importUser->method('getComptesCrees')->willReturn([]);
        static::getContainer()->set(ImportUser::class, $importUser);

        $crawler = $client->request('GET', '/admin/import-utilisateurs');
        $csrfToken = $crawler->filter('input[name="import_utilisateurs[_token]"]')->attr('value');

        $uploadedFile = $this->createCsvUpload("nom;prenom;mail\n");

        $client->request(
            'POST',
            '/admin/import-utilisateurs',
            [
                'import_utilisateurs' => [
                    'defaultRole' => '',
                    '_token' => $csrfToken,
                ],
            ],
            [
                'import_utilisateurs' => [
                    'fichier' => $uploadedFile,
                ],
            ],
            [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($payload['success']);
        $this->assertSame(ImportErrorType::AucunCompte->value, $payload['errorType']);
    }

    public function testImportAjaxRejetteExtensionNonCsv(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/import-utilisateurs');
        $csrfToken = $crawler->filter('input[name="import_utilisateurs[_token]"]')->attr('value');

        $path = tempnam(sys_get_temp_dir(), 'not_csv_');
        file_put_contents($path, 'hello');
        $this->tempFiles[] = $path;
        $uploadedFile = new UploadedFile($path, 'image.png', 'image/png', null, true);

        $client->request(
            'POST',
            '/admin/import-utilisateurs',
            [
                'import_utilisateurs' => [
                    'defaultRole' => '',
                    '_token' => $csrfToken,
                ],
            ],
            [
                'import_utilisateurs' => [
                    'fichier' => $uploadedFile,
                ],
            ],
            [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $this->assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($payload['success']);
        $this->assertSame('validation', $payload['errorType']);
    }

    public function testTelechargementModeleCsv(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request('GET', '/admin/import-utilisateurs/modele');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            'modele_import_utilisateurs.csv',
            (string) $client->getResponse()->headers->get('Content-Disposition')
        );
        $this->assertStringContainsString('nom;prenom;mail;role', (string) $client->getResponse()->getContent());
    }

    public function testTelechargementSansJetonValideRedirigeAvecErreur(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $crawler = $client->request('GET', '/admin/import-utilisateurs');
        $downloadCsrfToken = $crawler->filter('form[data-import-users-target="downloadForm"] input[name="_csrf_token"]')->attr('value');

        $client->request('POST', '/admin/import-utilisateurs/telecharger', [
            '_csrf_token' => $downloadCsrfToken,
            'token' => 'invalide',
        ]);

        $this->assertResponseRedirects('/admin/import-utilisateurs');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('expiré', $crawler->filter('#flash-messages')->text());
    }

    public function testTelechargementCsrfInvalideRedirigeAvecErreur(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $client->request('POST', '/admin/import-utilisateurs/telecharger', [
            '_csrf_token' => 'mauvais-token',
            'token' => 'quelconque',
        ]);

        $this->assertResponseRedirects('/admin/import-utilisateurs');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Jeton de sécurité invalide', $crawler->filter('#flash-messages')->text());
    }

    public function testErreurExportIdentifiantsRedirigeAvecFlash(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $importUser = $this->createStub(ImportUser::class);
        $importUser->method('getComptesCrees')->willReturn([
            ['nom' => 'Dupont', 'prenom' => 'Jean', 'username' => 'dupont.jean', 'password' => 'secret12'],
        ]);
        static::getContainer()->set(ImportUser::class, $importUser);

        $export = $this->createStub(ExportIdentifiants::class);
        $export->method('exporter')->willThrowException(
            new ImportException(ImportErrorType::EcritureImpossible, 'Dossier temporaire inaccessible en écriture.')
        );
        static::getContainer()->set(ExportIdentifiants::class, $export);

        $crawler = $client->request('GET', '/admin/import-utilisateurs');
        $csrfToken = $crawler->filter('input[name="import_utilisateurs[_token]"]')->attr('value');
        $downloadCsrfToken = $crawler->filter('form[data-import-users-target="downloadForm"] input[name="_csrf_token"]')->attr('value');

        $uploadedFile = $this->createCsvUpload("nom;prenom;mail\nDupont;Jean;jean@x.fr\n");
        $client->request(
            'POST',
            '/admin/import-utilisateurs',
            [
                'import_utilisateurs' => [
                    'defaultRole' => '',
                    '_token' => $csrfToken,
                ],
            ],
            [
                'import_utilisateurs' => [
                    'fichier' => $uploadedFile,
                ],
            ],
            [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'HTTP_ACCEPT' => 'application/json',
            ]
        );
        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $client->request('POST', '/admin/import-utilisateurs/telecharger', [
            '_csrf_token' => $downloadCsrfToken,
            'token' => $payload['token'],
        ]);

        $this->assertResponseRedirects('/admin/import-utilisateurs');
        $crawler = $client->followRedirect();
        $this->assertStringContainsString('Dossier temporaire inaccessible en écriture.', $crawler->filter('#flash-messages')->text());
    }
}
