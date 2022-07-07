<?php

namespace App\Form;

use App\Entity\Adress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Quel nom souhaitez- vous donner à votre adresse ?',
                'attr' => [
                    'placeholder' => 'Nommez votre adresse'
                ]
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Votre prénom :',
                'attr' => [
                    'placeholder' => 'Entrez votre prénom'
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Votre nom :',
                'attr' => [
                    'placeholder' => 'Entrez votre nom'
                ]
            ])
            ->add('company', TextType::class, [
                'label' => 'Votre société :',
                'attr' => [
                    'placeholder' => '(facultatif) Entrez le nom de votre société'
                ]
            ])
            ->add('adress', TextType::class, [
                'label' => 'Votre adresse :',
                'attr' => [
                    'placeholder' => '8 rue des lylas ...'
                ]
            ])
            ->add('postal', TextType::class, [
                'label' => 'Votre code postal :',
                'attr' => [
                    'placeholder' => '38870'
                ]
            ])
            ->add('city', TextType::class, [
                'label' => 'Votre ville :',
                'attr' => [
                    'placeholder' => 'Saint Siméon de Bressieux'
                ]
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays :',
                'attr' => [
                    'placeholder' => 'France'
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Votre télephone :',
                'attr' => [
                    'placeholder' => '0637732882'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider',
                'attr' => [
                    'class' => 'btn-block btn-info'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Adress::class,
        ]);
    }
}
