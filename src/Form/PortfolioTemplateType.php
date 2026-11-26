<?php

namespace App\Form;

use App\Entity\Annee;
use App\Entity\ApcNiveau;
use App\Entity\ApcParcours;
use App\Entity\ApcReferentiel;
use App\Entity\Departement;
use App\Entity\PortfolioTemplate;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PortfolioTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Departement|null $departement */
        $departement = $options['departement'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du modèle',
                'label_attr' => ['class' => 'form-label'],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. Portfolio de stage BUT2',
                    'maxlength' => 255,
                ],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Objectifs pédagogiques et contexte du portfolio guidé...',
                ],
            ])
            ->add('apcReferentiel', EntityType::class, [
                'class' => ApcReferentiel::class,
                'required' => false,
                'placeholder' => 'Sélectionner un référentiel (PN)',
                'label' => 'Référentiel (PN)',
                'label_attr' => ['class' => 'form-label'],
                'choice_label' => function (ApcReferentiel $referentiel): string {
                    $anneePub = $referentiel->getAnneePublication();

                    return $anneePub !== null
                        ? sprintf('%s (%d)', $referentiel->getLibelle() ?? 'Référentiel', $anneePub)
                        : ($referentiel->getLibelle() ?? 'Référentiel');
                },
                'query_builder' => function (EntityRepository $er) use ($departement): QueryBuilder {
                    $qb = $er->createQueryBuilder('r')
                        ->orderBy('r.annee_publication', 'DESC')
                        ->addOrderBy('r.libelle', 'ASC');

                    if ($departement !== null) {
                        $qb->andWhere('r.departement = :departement')
                            ->setParameter('departement', $departement);
                    }

                    return $qb;
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('annee', EntityType::class, [
                'class' => Annee::class,
                'required' => false,
                'placeholder' => 'Toutes les années',
                'label' => 'Année de BUT ciblée',
                'label_attr' => ['class' => 'form-label'],
                'choice_label' => fn (Annee $annee): string =>
                    $annee->getLibelleLong() ?? $annee->getLibelle() ?? sprintf('Année #%d', $annee->getId() ?? 0),
                'query_builder' => function (EntityRepository $er) use ($departement): QueryBuilder {
                    $qb = $er->createQueryBuilder('a')
                        ->leftJoin('a.diplome', 'd')
                        ->orderBy('a.ordre', 'ASC')
                        ->addOrderBy('a.libelle', 'ASC');

                    if ($departement !== null) {
                        $qb->andWhere('d.departement = :departement')
                            ->setParameter('departement', $departement);
                    }

                    return $qb;
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('apcParcours', EntityType::class, [
                'class' => ApcParcours::class,
                'required' => false,
                'placeholder' => 'Tous les parcours (optionnel)',
                'label' => 'Parcours ciblé (optionnel)',
                'label_attr' => ['class' => 'form-label'],
                'choice_label' => function (ApcParcours $parcours): string {
                    $code = $parcours->getCode();

                    return $code !== null && $code !== ''
                        ? sprintf('%s (%s)', $parcours->getLibelle() ?? '', $code)
                        : ($parcours->getLibelle() ?? '');
                },
                'query_builder' => function (EntityRepository $er) use ($departement): QueryBuilder {
                    $qb = $er->createQueryBuilder('p')
                        ->leftJoin('p.ApcReferentiel', 'r')
                        ->orderBy('p.libelle', 'ASC');

                    if ($departement !== null) {
                        $qb->andWhere('r.departement = :departement')
                            ->setParameter('departement', $departement);
                    }

                    return $qb;
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('competencesCibles', EntityType::class, [
                'class' => ApcNiveau::class,
                'multiple' => true,
                'required' => false,
                'label' => 'Compétences / niveaux ciblés',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs niveaux de compétence.',
                'choice_label' => fn (ApcNiveau $niveau): string =>
                    TemplateSectionType::buildNiveauChoiceLabel($niveau),
                'query_builder' => fn (EntityRepository $er): QueryBuilder =>
                    TemplateSectionType::createNiveauQueryBuilder($er, $departement),
                'attr' => [
                    'class' => 'form-select',
                    'size' => 6,
                ],
            ])
            ->add('sections', CollectionType::class, [
                'entry_type' => TemplateSectionType::class,
                'entry_options' => [
                    'departement' => $departement,
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PortfolioTemplate::class,
            'departement' => null,
        ]);
        $resolver->setAllowedTypes('departement', [Departement::class, 'null']);
    }
}
