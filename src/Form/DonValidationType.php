<?php

namespace App\Form;

use App\Entity\Don;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class DonValidationType extends AbstractType
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
                    new NotBlank(['message' => 'La date est obligatoire'])
                ]
            ])
            ->add('typeDon', ChoiceType::class, [
                'label' => 'Type de don',
                'choices' => [
                    'Sang total' => 'Sang total',
                    'Plasma' => 'Plasma',
                    'Plaquettes' => 'Plaquettes',
                    'Globules rouges' => 'Globules rouges',
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Le type de don est obligatoire'])
                ]
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité (ml)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '450',
                    'min' => 1
                ],
                'data' => 450,
                'constraints' => [
                    new NotBlank(['message' => 'La quantité est obligatoire']),
                    new Positive(['message' => 'La quantité doit être positive'])
                ]
            ])
            ->add('apte', ChoiceType::class, [
                'label' => 'Le donateur est-il apte ?',
                'choices' => [
                    'Apte' => true,
                    'Non apte' => false,
                ],
                'expanded' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez indiquer si le donateur est apte'])
                ]
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Observations médicales...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Don::class,
        ]);
    }
}