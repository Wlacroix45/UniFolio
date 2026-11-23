<?php

namespace App\Form;

use App\Entity\Departement;
use App\Service\ImportUser;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class ImportUtilisateursType extends AbstractType
{
    public const ROLE_AUTO = '';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('defaultRole', ChoiceType::class, [
                'label' => 'Rôle à attribuer',
                'required' => false,
                'placeholder' => false,
                'empty_data' => self::ROLE_AUTO,
                'choices' => [
                    'Automatique (colonne « role » du CSV ou domaine étudiant)' => self::ROLE_AUTO,
                    'Étudiant (ROLE_ETUDIANT)' => ImportUser::ROLE_ETUDIANT,
                    'Enseignant — Évaluateur (ROLE_EVALUATEUR)' => ImportUser::ROLE_EVALUATEUR,
                    'Enseignant — Éditeur (ROLE_EDITEUR)' => ImportUser::ROLE_EDITEUR,
                ],
                'help' => 'Utilisé lorsque la colonne « role » est absente ou vide. Si le CSV contient une colonne « role », celle-ci reste prioritaire.',
                'constraints' => [
                    new Choice(
                        choices: [
                            self::ROLE_AUTO,
                            ImportUser::ROLE_ETUDIANT,
                            ImportUser::ROLE_EVALUATEUR,
                            ImportUser::ROLE_EDITEUR,
                        ],
                        message: 'Le rôle sélectionné est invalide.',
                    ),
                ],
            ])
            ->add('defaultDepartement', EntityType::class, [
                'class' => Departement::class,
                'choice_label' => 'libelle',
                'label' => 'Département par défaut (enseignants)',
                'required' => false,
                'placeholder' => 'Aucun (colonne « departement » du CSV)',
                'help' => 'Attribué par défaut aux enseignants importés lorsque la colonne « departement » est absente ou vide.',
            ])
            ->add('fichier', FileType::class, [
                'label' => 'Fichier CSV des utilisateurs',
                'required' => true,
                'attr' => [
                    'accept' => '.csv,text/csv',
                ],
                'constraints' => [
                    new NotNull(message: 'Veuillez sélectionner ou déposer un fichier CSV.'),
                    new File(
                        maxSize: '10M',
                        maxSizeMessage: 'Le fichier est trop volumineux ({{ size }} {{ suffix }}). Taille maximale autorisée : {{ limit }} {{ suffix }}.',
                        extensions: [
                            'csv' => [
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'text/x-comma-separated-values',
                                'text/x-csv',
                                'application/vnd.ms-excel',
                            ],
                        ],
                        extensionsMessage: 'Format invalide ({{ extension }}). Seuls les fichiers .csv sont acceptés.',
                        disallowEmptyMessage: 'Le fichier CSV sélectionné est vide.',
                    ),
                ],
            ]);
    }
}
