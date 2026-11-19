<?php

namespace App\Form;

use App\Entity\Departement;
use App\Entity\EnseignantDepartement;
use App\Enum\DepartementRoleEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire d'association entre un enseignant, un département et son rôle dans ce département.
 */
class EnseignantDepartementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('departement', EntityType::class, [
                'class' => Departement::class,
                'choice_label' => 'libelle',
                'label' => 'Département',
                'placeholder' => 'Choisir un département',
                'required' => true,
            ])
            ->add('role', EnumType::class, [
                'class' => DepartementRoleEnum::class,
                'choice_label' => static fn (DepartementRoleEnum $role): string => $role->getLibelle(),
                'label' => 'Rôle dans ce département',
                'required' => true,
            ])
            ->add('defaut', CheckboxType::class, [
                'label' => 'Département par défaut',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EnseignantDepartement::class,
        ]);
    }
}
