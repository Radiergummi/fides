<?php

namespace App\Form;

use App\Entity\SecurityZone;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * EditUserType
 *
 * @method User getData()
 * @bundle App\Form
 */
class EditUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'help' => 'The full name of the user. Only used for display purposes.',
            ])
            ->add('email', EmailType::class)
            ->add('roles', ChoiceType::class, [
                'label' => 'Role',
                'choices' => [
                    'User' => User::ROLE_USER,
                    'Admin' => User::ROLE_ADMIN,
                ],
            ])
            ->add('securityZones', EntityType::class, [
                'multiple' => true,
                'expanded' => true,
                'class' => SecurityZone::class,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Update User',
            ]);

        $builder
            ->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                fn($rolesArray) => $rolesArray[0],
                fn($rolesString) => [$rolesString]
            ));
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
