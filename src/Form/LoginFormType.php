<?php

namespace App\Form;

use App\Security\FidesAuthenticator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class LoginFormType extends AbstractType
{
    public const FIELD_CSRF_TOKEN = '_csrf_token';

    public const FIELD_PASSWORD = '_password';

    public const FIELD_REMEMBER_ME = '_remember_me';

    public const FIELD_TARGET_PATH = '_target_path';

    public const FIELD_USERNAME = '_username';

    public function __construct(private AuthenticationUtils $authenticationUtils)
    {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(self::FIELD_USERNAME, EmailType::class, [
                'required' => true,
                'label' => 'Email Address',
            ])
            ->add(self::FIELD_PASSWORD, PasswordType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                ],
            ])
            ->add(self::FIELD_REMEMBER_ME, CheckboxType::class, [
                'label' => 'Remember me',
                'required' => false,
                'data' => false,
                'mapped' => false,
            ])
            ->add(self::FIELD_TARGET_PATH, HiddenType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'Sign in',
            ]);

        $authUtils = $this->authenticationUtils;

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($authUtils) {
                // get the login error if there is one
                $error = $authUtils->getLastAuthenticationError();

                if ($error) {
                    $event->getForm()->addError(new FormError(
                        $error->getMessageKey()
                    ));
                }

                $event->setData(array_replace(
                    (array)$event->getData(),
                    [
                        self::FIELD_USERNAME => $authUtils->getLastUsername(),
                    ]
                ));
            }
        );
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        /* Note: the form's csrf_token_id must correspond to that for the form
         * login listener in order for the CSRF token to validate successfully.
         */

        $resolver->setDefaults([
            'csrf_field_name' => self::FIELD_CSRF_TOKEN,
            'csrf_token_id' => FidesAuthenticator::CSRF_TOKEN_ID,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
