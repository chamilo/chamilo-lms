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
 * The verb in a {@link Statement}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class Verb
{
    private $id;
    private $display;

    public function __construct(IRI $id, LanguageMap $display = null)
    {
        $this->id = $id;
        $this->display = $display;
    }

    /**
     * Returns the verb definition reference.
     */
    public function getId(): IRI
    {
        return $this->id;
    }

    /**
     * Returns the human readable representation of the Verb in one or more languages.
     */
    public function getDisplay(): ?LanguageMap
    {
        return $this->display;
    }

    /**
     * Checks if another verb is equal.
     *
     * Two verbs are equal if and only if all of their properties are equal.
     */
    public function equals(Verb $verb): bool
    {
        if (!$this->id->equals($verb->id)) {
            return false;
        }

        if (null === $this->display && null === $verb->display) {
            return true;
        }

        if (null !== $this->display xor null !== $verb->display) {
            return false;
        }

        if (count($this->display) !== count($verb->getDisplay())) {
            return false;
        }

        foreach ($this->display as $language => $value) {
            if (!isset($verb->display[$language])) {
                return false;
            }

            if ($value !== $verb->display[$language]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tests if the Verb can be used to void a Statement.
     */
    public function isVoidVerb(): bool
    {
        return $this->id->equals(IRI::fromString('http://adlnet.gov/expapi/verbs/voided'));
    }

    /**
     * Creates a Verb that can be used to void a {@link Statement}.
     */
    public static function createVoidVerb(): self
    {
        return new Verb(IRI::fromString('http://adlnet.gov/expapi/verbs/voided'));
    }
}
