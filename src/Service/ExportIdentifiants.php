<?php

namespace App\Service;

use App\Exception\ImportErrorType;
use App\Exception\ImportException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExportIdentifiants
{
    public const COLONNES = ['nom', 'prenom', 'username', 'password'];

    public function __construct(private readonly string $dossierTemp = '')
    {
    }

    public function exporter(array $comptes): BinaryFileResponse
    {
        $this->verifier($comptes);

        $dossier = '' === $this->dossierTemp ? sys_get_temp_dir() : $this->dossierTemp;
        if (!is_dir($dossier) || !is_writable($dossier)) {
            throw new ImportException(ImportErrorType::EcritureImpossible, 'Dossier temporaire inaccessible en écriture.');
        }

        $path = tempnam($dossier, 'comptes_');
        $envoye = false;
        try {
            $this->ecrire($path, $comptes);
            $envoye = true;
        } finally {
            // le fichier ne survit jamais a une erreur
            if (!$envoye) {
                @unlink($path);
            }
        }

        // supprime par symfony une fois la reponse envoyee
        return (new BinaryFileResponse($path))
            ->deleteFileAfterSend(true)
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'comptes.csv');
    }

    private function verifier(array $comptes): void
    {
        if ([] === $comptes) {
            throw new ImportException(ImportErrorType::AucunCompte, 'Aucun compte à exporter.');
        }

        foreach ($comptes as $i => $compte) {
            foreach (self::COLONNES as $colonne) {
                if (!is_string($compte[$colonne] ?? null) || '' === $compte[$colonne]) {
                    throw new ImportException(
                        ImportErrorType::DonneesInvalides,
                        sprintf('Compte %d invalide : champ "%s" manquant ou vide.', $i + 1, $colonne)
                    );
                }
            }
        }
    }

    private function ecrire(string $path, array $comptes): void
    {
        $handle = fopen($path, 'w');
        if (false === $handle) {
            throw new ImportException(ImportErrorType::EcritureImpossible, "Impossible d'ouvrir le fichier CSV.");
        }

        try {
            $lignes = [self::COLONNES, ...array_map(static fn (array $c): array => array_map(
                static fn (string $colonne): string => $c[$colonne],
                self::COLONNES
            ), $comptes)];
            foreach ($lignes as $ligne) {
                if (false === fputcsv($handle, $ligne, ',', '"', '')) {
                    throw new ImportException(ImportErrorType::EcritureImpossible, "Échec d'écriture du fichier CSV.");
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
