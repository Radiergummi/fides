<?php

namespace App\Form;

use App\Entity\SecurityZone;
use App\Entity\User;
use App\Entity\UserCertificate;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function assert;

/**
 * RequestUserCertificateType
 *
 * @method UserCertificate getData()
 * @bundle App\Form
 */
class RequestUserCertificateType extends AbstractType
{
    private User $user;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $user = $tokenStorage
            ->getToken()
            ?->getUser();

        assert($user instanceof User);

        $this->user = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('publicKey', TextareaType::class, [
                'help' => 'Content of the public key file to sign.',
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAICkWqU5e59bG5m+6U2fYxTtO7tYBBsgYxWNbptj1H9GO Example key',
                ],
            ])
            ->add('validFrom', DateTimeType::class, [
                'help' => 'Timestamp the certificate will be valid from.',
                'input' => 'datetime_immutable',
                'default' => new DateTimeImmutable(),
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'with_seconds' => true,
            ])
            ->add('validUntil', DateTimeType::class, [
                'help' => 'Timestamp the certificate will be valid until.',
                'input' => 'datetime_immutable',
                'default' => (new DateTimeImmutable())->add(new DateInterval(
                    'PT24H'
                )),
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'with_seconds' => true,
            ])
            ->add('securityZones', EntityType::class, [
                'help' => 'Security zones to be issued for the certificate',
                'multiple' => true,
                'expanded' => true,
                'class' => SecurityZone::class,
                'default' => $this->user->getSecurityZones(),
                'query_builder' => fn(
                    EntityRepository $repository
                ): QueryBuilder => $repository
                    ->createQueryBuilder('s')
                    ->join(User::class, 'u')
                    ->where('u.id = :user')
                    ->setParameter('user', $this->user->getId()),
            ])
            ->add('forceCommand', TextType::class, [
                'label' => 'Enforced Command',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Optional',
                ],
                'help' => 'Forces the execution of this command instead of ' .
                          'any shell or command specified by the user when ' .
                          'the certificate is used for authentication.',
            ])
            ->add('agentForwardingEnabled', CheckboxType::class, [
                'label' => 'Agent Forwarding',
                'required' => false,
                'data' => true,
            ])
            ->add('portForwardingEnabled', CheckboxType::class, [
                'label' => 'Port Forwarding',
                'required' => false,
                'data' => true,
            ])
            ->add('x11ForwardingEnabled', CheckboxType::class, [
                'label' => 'X11 Forwarding',
                'required' => false,
                'data' => true,
            ])
            ->add('ptyAllocationEnabled', CheckboxType::class, [
                'label' => 'PTY Allocation',
                'required' => false,
                'data' => true,
            ])
            ->add('userRcExecutionEnabled', CheckboxType::class, [
                'label' => 'User RC File Execution',
                'required' => false,
                'data' => true,
            ])
            ->add('allowedSourceAddresses', TextareaType::class, [
                'label' => 'Source IP Allow-list',
                'required' => false,
                'attr' => [
                    'placeholder' => "10.0.0.0/24 \r\n182.12.84.2",
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Request Certificate',
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserCertificate::class,
        ]);
    }
}
