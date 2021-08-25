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
 * Definition of an {@link Activity}.
 *
 * A number of derived classes exists each of them covering a specialized
 * type of user interaction:
 *
 * <ul>
 *   <li>ChoiceInteractionDefinition</li>
 *   <li>FillInteractionDefinition</li>
 *   <li>LikertInteractionDefinition</li>
 *   <li>LongFillInInteractionDefinition</li>
 *   <li>MatchingInteractionDefinition</li>
 *   <li>NumericInteractionDefinition</li>
 *   <li>PerformanceInteractionDefinition</li>
 *   <li>OtherInteractionDefinition</li>
 *   <li>SequencingInteractionDefinition</li>
 *   <li>TrueFalseInteractionDefinition</li>
 * </ul>
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class Definition
{
    private $name;
    private $description;
    private $type;
    private $moreInfo;
    private $extensions;

    public function __construct(LanguageMap $name = null, LanguageMap $description = null, IRI $type = null, IRL $moreInfo = null, Extensions $extensions = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->moreInfo = $moreInfo;
        $this->extensions = $extensions;
    }

    public function withName(LanguageMap $name = null): self
    {
        $definition = clone $this;
        $definition->name = $name;

        return $definition;
    }

    public function withDescription(LanguageMap $description = null): self
    {
        $definition = clone $this;
        $definition->description = $description;

        return $definition;
    }

    public function withType(IRI $type = null): self
    {
        $definition = clone $this;
        $definition->type = $type;

        return $definition;
    }

    public function withMoreInfo(IRL $moreInfo = null): self
    {
        $definition = clone $this;
        $definition->moreInfo = $moreInfo;

        return $definition;
    }

    public function withExtensions(Extensions $extensions): self
    {
        $definition = clone $this;
        $definition->extensions = $extensions;

        return $definition;
    }

    /**
     * Returns the human readable names.
     */
    public function getName(): ?LanguageMap
    {
        return $this->name;
    }

    /**
     * Returns the human readable descriptions.
     */
    public function getDescription(): ?LanguageMap
    {
        return $this->description;
    }

    /**
     * Returns the {@link Activity} type.
     */
    public function getType(): ?IRI
    {
        return $this->type;
    }

    /**
     * Returns an IRL where human-readable information about the activity can be found.
     */
    public function getMoreInfo(): ?IRL
    {
        return $this->moreInfo;
    }

    public function getExtensions(): ?Extensions
    {
        return $this->extensions;
    }

    /**
     * Checks if another definition is equal.
     *
     * Two definitions are equal if and only if all of their properties are equal.
     */
    public function equals(Definition $definition): bool
    {
        if (get_class($this) !== get_class($definition)) {
            return false;
        }

        if (null !== $this->type xor null !== $definition->type) {
            return false;
        }

        if (null !== $this->type && null !== $definition->type && !$this->type->equals($definition->type)) {
            return false;
        }

        if (null !== $this->moreInfo xor null !== $definition->moreInfo) {
            return false;
        }

        if (null !== $this->moreInfo && null !== $definition->moreInfo && !$this->moreInfo->equals($definition->moreInfo)) {
            return false;
        }

        if (null !== $this->extensions xor null !== $definition->extensions) {
            return false;
        }

        if (null !== $this->name xor null !== $definition->name) {
            return false;
        }

        if (null !== $this->description xor null !== $definition->description) {
            return false;
        }

        if (null !== $this->name) {
            if (count($this->name) !== count($definition->name)) {
                return false;
            }

            foreach ($this->name as $language => $value) {
                if (!isset($definition->name[$language])) {
                    return false;
                }

                if ($value !== $definition->name[$language]) {
                    return false;
                }
            }
        }

        if (null !== $this->description) {
            if (count($this->description) !== count($definition->description)) {
                return false;
            }

            foreach ($this->description as $language => $value) {
                if (!isset($definition->description[$language])) {
                    return false;
                }

                if ($value !== $definition->description[$language]) {
                    return false;
                }
            }
        }

        if (null !== $this->extensions && null !== $definition->extensions && !$this->extensions->equals($definition->extensions)) {
            return false;
        }

        return true;
    }
}
