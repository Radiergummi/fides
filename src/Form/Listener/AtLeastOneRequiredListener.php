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

namespace App\Form\Listener;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use function is_array;
use function is_callable;
use function ucfirst;

class AtLeastOneRequiredListener implements EventSubscriberInterface
{
    /**
     * @var string[]
     */
    private array $fieldsToCheck;

    public function __construct(
        string $firstFieldToCheck,
        string $secondFieldToCheck,
        string ...$additionalFieldsToCheck
    ) {
        $this->fieldsToCheck = [
                                   $firstFieldToCheck,
                                   $secondFieldToCheck,
                               ] + $additionalFieldsToCheck;
    }

    #[ArrayShape([FormEvents::SUBMIT => 'string'])]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => 'onSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     *
     * @throws TransformationFailedException
     */
    public function onSubmit(FormEvent $event): void
    {
        $submittedData = $event->getData();
        $emptyFields = [];

        foreach ($this->fieldsToCheck as $fieldToCheck) {
            if (
                is_array($submittedData) &&
                ! isset($submittedData[$fieldToCheck])
            ) {
                $emptyFields[] = $fieldToCheck;
            } else {
                $getter = 'get' . ucfirst($fieldToCheck);

                if (
                    is_callable([$submittedData, $getter]) &&
                    $submittedData->{$getter}() === null
                ) {
                    $emptyFields[] = $fieldToCheck;
                }
            }
        }

        if (count($emptyFields) === count($this->fieldsToCheck)) {
            throw new TransformationFailedException(sprintf(
                'at least one of %s is required',
                implode(', ', $this->fieldsToCheck)
            ));
        }
    }
}
