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
 * An Experience API {@link https://github.com/adlnet/xAPI-Spec/blob/master/xAPI.md#statement Statement}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class Statement
{
    private $id;
    private $verb;
    private $actor;
    private $object;
    private $result;
    private $authority;
    private $created;
    private $stored;
    private $context;
    private $attachments;
    private $version;

    /**
     * @param Attachment[]|null $attachments
     */
    public function __construct(StatementId $id = null, Actor $actor, Verb $verb, StatementObject $object, Result $result = null, Actor $authority = null, \DateTime $created = null, \DateTime $stored = null, Context $context = null, array $attachments = null, string $version = null)
    {
        $this->id = $id;
        $this->actor = $actor;
        $this->verb = $verb;
        $this->object = $object;
        $this->result = $result;
        $this->authority = $authority;
        $this->created = $created;
        $this->stored = $stored;
        $this->context = $context;
        $this->attachments = null !== $attachments ? array_values($attachments) : null;
        $this->version = $version;
    }

    public function withId(StatementId $id = null): self
    {
        $statement = clone $this;
        $statement->id = $id;

        return $statement;
    }

    public function withActor(Actor $actor): self
    {
        $statement = clone $this;
        $statement->actor = $actor;

        return $statement;
    }

    public function withVerb(Verb $verb): self
    {
        $statement = clone $this;
        $statement->verb = $verb;

        return $statement;
    }

    public function withObject(StatementObject $object): self
    {
        $statement = clone $this;
        $statement->object = $object;

        return $statement;
    }

    public function withResult(Result $result = null): self
    {
        $statement = clone $this;
        $statement->result = $result;

        return $statement;
    }

    /**
     * Creates a new Statement based on the current one containing an Authority
     * that asserts the Statement true.
     */
    public function withAuthority(Actor $authority = null): self
    {
        $statement = clone $this;
        $statement->authority = $authority;

        return $statement;
    }

    public function withCreated(\DateTime $created = null): self
    {
        $statement = clone $this;
        $statement->created = $created;

        return $statement;
    }

    public function withStored(\DateTime $stored = null): self
    {
        $statement = clone $this;
        $statement->stored = $stored;

        return $statement;
    }

    public function withContext(Context $context = null): self
    {
        $statement = clone $this;
        $statement->context = $context;

        return $statement;
    }

    /**
     * @param Attachment[]|null $attachments
     */
    public function withAttachments(array $attachments = null): self
    {
        $statement = clone $this;
        $statement->attachments = null !== $attachments ? array_values($attachments) : null;

        return $statement;
    }

    public function withVersion(string $version = null): self
    {
        $statement = clone $this;
        $statement->version = $version;

        return $statement;
    }

    /**
     * Returns the Statement's unique identifier.
     */
    public function getId(): ?StatementId
    {
        return $this->id;
    }

    /**
     * Returns the Statement's {@link Verb}.
     */
    public function getVerb(): Verb
    {
        return $this->verb;
    }

    /**
     * Returns the Statement's {@link Actor}.
     */
    public function getActor(): Actor
    {
        return $this->actor;
    }

    /**
     * Returns the Statement's {@link StatementObject}.
     */
    public function getObject(): StatementObject
    {
        return $this->object;
    }

    /**
     * Returns the {@link Activity} {@link Result}.
     */
    public function getResult(): ?Result
    {
        return $this->result;
    }

    /**
     * Returns the Authority that asserted the Statement true.
     */
    public function getAuthority(): ?Actor
    {
        return $this->authority;
    }

    /**
     * Returns the timestamp of when the events described in this statement
     * occurred.
     */
    public function getCreated(): ?\DateTime
    {
        return $this->created;
    }

    /**
     * Returns the timestamp of when this statement was recorded by the LRS.
     */
    public function getStored(): ?\DateTime
    {
        return $this->stored;
    }

    /**
     * Returns the context that gives the statement more meaning.
     */
    public function getContext(): ?Context
    {
        return $this->context;
    }

    /**
     * @return Attachment[]|null
     */
    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Tests whether or not this Statement is a void Statement (i.e. it voids
     * another Statement).
     */
    public function isVoidStatement(): bool
    {
        return $this->verb->isVoidVerb();
    }

    /**
     * Returns a {@link StatementReference} for the Statement.
     */
    public function getStatementReference(): StatementReference
    {
        return new StatementReference($this->id);
    }

    /**
     * Returns a Statement that voids the current Statement.
     */
    public function getVoidStatement(Actor $actor): self
    {
        return new Statement(
            null,
            $actor,
            Verb::createVoidVerb(),
            $this->getStatementReference()
        );
    }

    /**
     * Checks if another statement is equal.
     *
     * Two statements are equal if and only if all of their properties are equal.
     */
    public function equals(Statement $statement): bool
    {
        if (null !== $this->id xor null !== $statement->id) {
            return false;
        }

        if (null !== $this->id && null !== $statement->id && !$this->id->equals($statement->id)) {
            return false;
        }

        if (!$this->actor->equals($statement->actor)) {
            return false;
        }

        if (!$this->verb->equals($statement->verb)) {
            return false;
        }

        if (!$this->object->equals($statement->object)) {
            return false;
        }

        if (null === $this->result && null !== $statement->result) {
            return false;
        }

        if (null !== $this->result && null === $statement->result) {
            return false;
        }

        if (null !== $this->result && !$this->result->equals($statement->result)) {
            return false;
        }

        if (null === $this->authority && null !== $statement->authority) {
            return false;
        }

        if (null !== $this->authority && null === $statement->authority) {
            return false;
        }

        if (null !== $this->authority && !$this->authority->equals($statement->authority)) {
            return false;
        }

        if ($this->created != $statement->created) {
            return false;
        }

        if (null !== $this->context xor null !== $statement->context) {
            return false;
        }

        if (null !== $this->context && null !== $statement->context && !$this->context->equals($statement->context)) {
            return false;
        }

        if (null !== $this->attachments xor null !== $statement->attachments) {
            return false;
        }

        if (null !== $this->attachments && null !== $statement->attachments) {
            if (count($this->attachments) !== count($statement->attachments)) {
                return false;
            }

            foreach ($this->attachments as $key => $attachment) {
                if (!$attachment->equals($statement->attachments[$key])) {
                    return false;
                }
            }
        }

        return true;
    }
}
