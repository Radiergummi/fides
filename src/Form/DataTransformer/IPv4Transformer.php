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

namespace App\Form\DataTransformer;

use Darsyn\IP\Exception\InvalidIpAddressException;
use Darsyn\IP\Exception\WrongVersionException;
use Darsyn\IP\Version\IPv4;
use InvalidArgumentException;
use Symfony\Component\Form\DataTransformerInterface;

use function is_string;

class IPv4Transformer implements DataTransformerInterface
{
    /**
     * @param mixed $value
     *
     * @return IPv4|null
     * @throws InvalidIpAddressException
     * @throws WrongVersionException
     */
    public function reverseTransform(mixed $value): IPv4|null
    {
        if ($value === null) {
            return null;
        }

        if ( ! is_string($value)) {
            throw new InvalidIpAddressException($value);
        }

        return IPv4::factory($value);
    }

    /**
     * @param mixed $value
     *
     * @return string|null
     * @throws InvalidArgumentException
     */
    public function transform(mixed $value): string|null
    {
        if ($value === null) {
            return null;
        }

        if ( ! $value instanceof IPv4) {
            throw new InvalidArgumentException(
                'Not an IPv4 address instance'
            );
        }

        return (string)$value;
    }
}
