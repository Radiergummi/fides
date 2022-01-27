<?php

namespace App\Form;

use App\Entity\Host;
use App\Entity\SecurityZone;
use App\Form\DataTransformer\IPv4Transformer;
use App\Form\DataTransformer\IPv6Transformer;
use App\Form\Listener\AtLeastOneRequiredListener;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddHostType extends AbstractType
{
    public function __construct(
        private IPv4Transformer $ipv4Transformer,
        private IPv6Transformer $ipv6Transformer,
    ) {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullyQualifiedName', TextType::class, [
                'label' => 'Hostname',
            ])
            ->add('displayName', TextType::class, [
                'label' => 'Display Name',
                'help' => 'The display name is optional.',
            ])
            ->add('ipv4Address', TextType::class, [
                'label' => 'IPv4 Address',
                'required' => false,
                'attr' => [
                    'placeholder' => '10.0.0.42',
                ],
            ])
            ->add('ipv6Address', TextType::class, [
                'label' => 'IPv6 Address',
                'required' => false,
                'attr' => [
                    'placeholder' => '::ffaa',
                ],
            ])
            ->add('securityZones', EntityType::class, [
                'multiple' => true,
                'expanded' => true,
                'class' => SecurityZone::class,
            ])
            ->add('submit', SubmitType::class, [
            ]);

        $builder->addEventSubscriber(new AtLeastOneRequiredListener(
            'ipv4Address',
            'ipv6Address'
        ));

        $builder
            ->get('ipv4Address')
            ->addModelTransformer($this->ipv4Transformer);

        $builder
            ->get('ipv6Address')
            ->addModelTransformer($this->ipv6Transformer);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Host::class,
        ]);
    }
}
