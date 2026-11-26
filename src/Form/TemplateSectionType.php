<?php

namespace App\Form;

use App\Entity\ApcNiveau;
use App\Entity\Departement;
use App\Entity\TemplateSection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateSectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Departement|null $departement */
        $departement = $options['departement'];

        $builder
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre',
                'label_attr' => ['class' => 'form-label'],
                'attr' => [
                    'class' => 'form-control section-ordre-input',
                    'min' => 1,
                ],
            ])
            ->add('titre', TextType::class, [
                'label' => 'Titre de la section',
                'label_attr' => ['class' => 'form-label'],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. Analyse réflexive 1, Bilan de stage...',
                    'maxlength' => 255,
                ],
                'required' => true,
            ])
            ->add('consigne', TextareaType::class, [
                'label' => 'Consignes / description',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Attendus pédagogiques, nombre de traces conseillé, format...',
                ],
            ])
            ->add('questionReflexivePrompt', TextareaType::class, [
                'label' => 'Question réflexive (guide étudiant)',
                'label_attr' => ['class' => 'form-label'],
                'required' => false,
                'help' => 'Optionnel : consigne réflexive affichée à l\'étudiant pour cette section.',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Ex. Quelle compétence avez-vous mobilisée et comment justifiez-vous votre démarche ?',
                ],
            ])
            ->add('competencesCibles', EntityType::class, [
                'class' => ApcNiveau::class,
                'multiple' => true,
                'required' => false,
                'label' => 'Compétences / niveaux ciblés (optionnel)',
                'label_attr' => ['class' => 'form-label'],
                'help' => 'Si vide, les compétences ciblées du modèle seront appliquées par défaut.',
                'choice_label' => fn (ApcNiveau $niveau) => self::buildNiveauChoiceLabel($niveau),
                'query_builder' => fn (EntityRepository $er) => self::createNiveauQueryBuilder($er, $departement),
                'attr' => [
                    'class' => 'form-select',
                    'size' => 4,
                ],
            ]);
    }

    public static function buildNiveauChoiceLabel(ApcNiveau $niveau): string
    {
        $competence = $niveau->getCompetences();
        $competenceLabel = $competence?->getNomCourt() ?? $competence?->getLibelle() ?? '';
        $ordre = $niveau->getOrdre();

        return trim(sprintf(
            '%s%s%s',
            $competenceLabel !== '' ? $competenceLabel . ' - ' : '',
            $niveau->getLibelle() ?? '',
            $ordre !== null ? sprintf(' (Niv. %d)', $ordre) : ''
        ));
    }

    public static function createNiveauQueryBuilder(
        EntityRepository $er,
        ?Departement $departement
    ): QueryBuilder {
        $qb = $er->createQueryBuilder('n')
            ->leftJoin('n.competences', 'c')
            ->leftJoin('c.referentiel', 'r')
            ->orderBy('c.libelle', 'ASC')
            ->addOrderBy('n.ordre', 'ASC');

        if ($departement !== null) {
            $qb->andWhere('r.departement = :departement')
                ->setParameter('departement', $departement);
        }

        return $qb;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TemplateSection::class,
            'departement' => null,
        ]);
        $resolver->setAllowedTypes('departement', [Departement::class, 'null']);
    }
}
