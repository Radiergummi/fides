<?php

namespace App\Form;

use App\Entity\SecurityZone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddZoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('identifier', TextType::class, [
                'required' => true,
                'attr' => [
                    'placeholder' => 'web-servers',
                ],
            ])
            ->add('displayName', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Web Servers',
                ],
                'help' => 'The display name is optional.',
            ])
            ->add('principal', TextType::class, [
                'default' => SecurityZone::DEFAULT_PRINCIPAL,
                'required' => true,
                'help' => 'Name of the principal certificates should be issued for by default in this zone.'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Create Zone',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SecurityZone::class,
        ]);
    }
}
