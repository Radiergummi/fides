<?php

/**
 * This file is part of fides, a Matchory application.
 *
 * Unauthorized copying of this file, via any medium, is strictly prohibited.
 * Its contents are strictly confidential and proprietary.
 *
 * @copyright 2020–2022 Matchory GmbH · All rights reserved
 * @author    Moritz Friedrich <moritz@matchory.com>
 */

declare(strict_types=1);

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultFormTypeExtension extends AbstractTypeExtension
{
    public function getExtendedType(): string
    {
        return FormType::class;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @noinspection PhpVoidFunctionResultUsedInspection
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['default'] === null) {
            return;
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            fn(FormEvent $event) => $event->getData() === null
                ? $event->setData($options['default'])
                : null
        );
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('default', null);
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
