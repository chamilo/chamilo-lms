<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

/**
 * Contextual information for an xAPI statement.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class Context
{
    private $registration;
    private $instructor;
    private $team;
    private $contextActivities;
    private $revision;
    private $platform;
    private $language;
    private $statement;
    private $extensions;

    public function withRegistration(string $registration): self
    {
        $context = clone $this;
        $context->registration = $registration;

        return $context;
    }

    public function withInstructor(Actor $instructor): self
    {
        $context = clone $this;
        $context->instructor = $instructor;

        return $context;
    }

    public function withTeam(Group $team): self
    {
        $context = clone $this;
        $context->team = $team;

        return $context;
    }

    public function withContextActivities(ContextActivities $contextActivities): self
    {
        $context = clone $this;
        $context->contextActivities = $contextActivities;

        return $context;
    }

    public function withRevision(string $revision): self
    {
        $context = clone $this;
        $context->revision = $revision;

        return $context;
    }

    public function withPlatform(string $platform): self
    {
        $context = clone $this;
        $context->platform = $platform;

        return $context;
    }

    public function withLanguage(string $language): self
    {
        $context = clone $this;
        $context->language = $language;

        return $context;
    }

    public function withStatement(StatementReference $statement): self
    {
        $context = clone $this;
        $context->statement = $statement;

        return $context;
    }

    public function withExtensions(Extensions $extensions): self
    {
        $context = clone $this;
        $context->extensions = $extensions;

        return $context;
    }

    public function getRegistration(): ?string
    {
        return $this->registration;
    }

    public function getInstructor(): ?Actor
    {
        return $this->instructor;
    }

    public function getTeam(): ?Group
    {
        return $this->team;
    }

    public function getContextActivities(): ?ContextActivities
    {
        return $this->contextActivities;
    }

    public function getRevision(): ?string
    {
        return $this->revision;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getStatement(): ?StatementReference
    {
        return $this->statement;
    }

    public function getExtensions(): ?Extensions
    {
        return $this->extensions;
    }

    public function equals(Context $context): bool
    {
        if ($this->registration !== $context->registration) {
            return false;
        }

        if (null !== $this->instructor xor null !== $context->instructor) {
            return false;
        }

        if (null !== $this->instructor && null !== $context->instructor && !$this->instructor->equals($context->instructor)) {
            return false;
        }

        if (null !== $this->team xor null !== $context->team) {
            return false;
        }

        if (null !== $this->team && null !== $context->team && !$this->team->equals($context->team)) {
            return false;
        }

        if ($this->contextActivities != $context->contextActivities) {
            return false;
        }

        if ($this->revision !== $context->revision) {
            return false;
        }

        if ($this->platform !== $context->platform) {
            return false;
        }

        if ($this->language !== $context->language) {
            return false;
        }

        if (null !== $this->statement xor null !== $context->statement) {
            return false;
        }

        if (null !== $this->statement && null !== $context->statement && !$this->statement->equals($context->statement)) {
            return false;
        }

        if (null !== $this->extensions xor null !== $context->extensions) {
            return false;
        }

        if (null !== $this->extensions && null !== $context->extensions && !$this->extensions->equals($context->extensions)) {
            return false;
        }

        return true;
    }
}
