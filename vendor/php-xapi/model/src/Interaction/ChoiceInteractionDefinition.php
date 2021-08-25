<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model\Interaction;

use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\Extensions;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;

/**
 * An interaction with a number of possible choices from which the learner
 * can select.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class ChoiceInteractionDefinition extends InteractionDefinition
{
    private $choices;

    /**
     * @param string[]|null               $correctResponsesPattern
     * @param InteractionComponent[]|null $choices
     */
    public function __construct(LanguageMap $name = null, LanguageMap $description = null, IRI $type = null, IRL $moreInfo = null, Extensions $extensions = null, array $correctResponsesPattern = null, array $choices = null)
    {
        parent::__construct($name, $description, $type, $moreInfo, $extensions, $correctResponsesPattern);

        $this->choices = $choices;
    }

    /**
     * @param InteractionComponent[]|null $choices
     */
    public function withChoices(array $choices = null): self
    {
        $interaction = clone $this;
        $interaction->choices = $choices;

        return $interaction;
    }

    /**
     * @return InteractionComponent[]|null
     */
    public function getChoices(): ?array
    {
        return $this->choices;
    }

    public function equals(Definition $definition): bool
    {
        if (!parent::equals($definition)) {
            return false;
        }

        if (!$definition instanceof ChoiceInteractionDefinition) {
            return false;
        }

        if (null !== $this->choices xor null !== $definition->choices) {
            return false;
        }

        if (null !== $this->choices) {
            if (count($this->choices) !== count($definition->choices)) {
                return false;
            }

            foreach ($this->choices as $key => $choice) {
                if (!isset($definition->choices[$key])) {
                    return false;
                }

                if (!$choice->equals($definition->choices[$key])) {
                    return false;
                }
            }
        }

        return true;
    }
}
