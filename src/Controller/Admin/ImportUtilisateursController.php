<?php

namespace App\Controller\Admin;

use App\Exception\ImportErrorType;
use App\Exception\ImportException;
use App\Form\ImportUtilisateursType;
use App\Service\ExportIdentifiants;
use App\Service\ImportUser;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[AdminRoute(path: '/import-utilisateurs', name: 'import_users')]
class ImportUtilisateursController extends AbstractController
{
    private const SESSION_EXPORT_KEY = '_admin_import_users_export';
    private const EXPORT_TTL_SECONDS = 1800;

    public function __construct(
        private readonly ImportUser $importUser,
        private readonly ExportIdentifiants $exportIdentifiants,
    ) {
    }

    #[AdminRoute(path: '', name: 'index', options: ['methods' => ['GET', 'POST']])]
    public function index(Request $request): Response
    {
        $form = $this->createForm(ImportUtilisateursType::class);
        if (!$request->isMethod('POST')) {
            return $this->render('Admin/import_users.html.twig', ['form' => $form]);
        }

        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return $this->json(
                ['success' => false, 'errorType' => 'validation', 'message' => 'La requête est invalide ou le fichier dépasse la taille maximale autorisée.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (!$form->isValid()) {
            $errors = iterator_to_array($form->getErrors(true), false);

            return $this->json(
                ['success' => false, 'errorType' => 'validation', 'message' => $errors[0]?->getMessage() ?? 'Le formulaire envoyé est invalide.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $form->get('fichier')->getData();
        $selectedRole = (string) ($form->get('defaultRole')->getData() ?? '');
        $defaultRole = ImportUtilisateursType::ROLE_AUTO === $selectedRole ? null : $selectedRole;

        try {
            $this->importUser->importerCsv($uploadedFile->getPathname(), defaultRole: $defaultRole);
            $comptes = $this->importUser->getComptesCrees();
            if ([] === $comptes) {
                throw new ImportException(ImportErrorType::AucunCompte, "Le fichier CSV ne contient aucune ligne d'utilisateur à importer.");
            }
        } catch (ImportException $e) {
            return $this->json(
                ['success' => false, 'errorType' => $e->getType()->value, 'message' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $token = bin2hex(random_bytes(16));
        $request->getSession()->set(self::SESSION_EXPORT_KEY, [
            'token' => $token,
            'comptes' => $comptes,
            'created_at' => time(),
        ]);

        return new JsonResponse([
            'success' => true,
            'count' => count($comptes),
            'token' => $token,
        ]);
    }

    #[AdminRoute(path: '/telecharger', name: 'download', options: ['methods' => ['POST']])]
    public function download(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('import_users_download', (string) $request->request->get('_csrf_token', ''))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide. Veuillez relancer le téléchargement.');

            return $this->redirectToRoute('admin_import_users_index');
        }

        $session = $request->getSession();
        $export = $session->get(self::SESSION_EXPORT_KEY);
        $token = (string) $request->request->get('token', '');

        if (
            !is_array($export)
            || '' === $token
            || !hash_equals((string) ($export['token'] ?? ''), $token)
            || (time() - (int) ($export['created_at'] ?? 0)) > self::EXPORT_TTL_SECONDS
        ) {
            $session->remove(self::SESSION_EXPORT_KEY);
            $this->addFlash('danger', 'Les identifiants générés ont expiré ou ne sont plus disponibles. Veuillez effectuer un nouvel import.');

            return $this->redirectToRoute('admin_import_users_index');
        }

        try {
            return $this->exportIdentifiants->exporter($export['comptes']);
        } catch (ImportException $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirectToRoute('admin_import_users_index');
        }
    }

    #[AdminRoute(path: '/modele', name: 'template', options: ['methods' => ['GET']])]
    public function template(): Response
    {
        $content = "\xEF\xBB\xBFnom;prenom;mail;role\nDupont;Jean;jean.dupont@etu.univ-orleans.fr;etudiant\nMartin;Claire;claire.martin@univ-reims.fr;enseignant\n";
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'modele_import_utilisateurs.csv'));

        return $response;
    }
}
