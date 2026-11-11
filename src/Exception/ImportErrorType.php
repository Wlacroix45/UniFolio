<?php

namespace App\Exception;

enum ImportErrorType: string
{
    case FichierIllisible = 'fichier_illisible';
    case ColonneManquante = 'colonne_manquante';
    case LigneIncomplete = 'ligne_incomplete';
    case AucunCompte = 'aucun_compte';
    case DonneesInvalides = 'donnees_invalides';
    case EcritureImpossible = 'ecriture_impossible';
    case ImportEchoue = 'import_echoue';
}
