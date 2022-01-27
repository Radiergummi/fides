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

namespace App\Entity\Behaviour;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;

trait TimestampTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable|null $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable|null $updatedAt = null;

    /**
     * @return DateTimeImmutable|null
     * @throws Exception
     */
    public function getCreatedAt(): DateTimeImmutable|null
    {
        return $this->createdAt ?? new DateTimeImmutable();
    }

    /**
     * @param DateTimeImmutable $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    abstract public function getId(): mixed;

    /**
     * @return DateTimeImmutable|null
     */
    public function getUpdatedAt(): DateTimeImmutable|null
    {
        return $this->updatedAt ?? new DateTimeImmutable();
    }

    /**
     * @param DateTimeImmutable $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $now = new DateTimeImmutable();

        $this->setUpdatedAt($now);

        if ($this->getId() === null) {
            $this->setCreatedAt($now);
        }
    }
}
