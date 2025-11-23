<?php

namespace App\Form;

use App\Entity\Don;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class DonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('datedon', DateType::class, [
                'label' => 'Date du don',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'data' => new \DateTime(),
                'constraints' => [
                    new NotBlank(['message' => 'La date du don est obligatoire']),
                ]
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité (en ml)',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Ex: 450'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La quantité est obligatoire']),
                    new Positive(['message' => 'La quantité doit être positive']),
                ]
            ])
            ->add('typeDon', TextType::class, [
                'label' => 'Type de don',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Sang total, Plasma, Plaquettes'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le type de don est obligatoire']),
                ]
            ])
            ->add('apte', CheckboxType::class, [
                'label' => 'Donateur apte',
                'required' => true,
                'attr' => ['class' => 'form-check-input'],
                'help' => 'Cochez si le donateur est apte au don',
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Observations médicales ou commentaires...'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Don::class,
        ]);
    }
}

