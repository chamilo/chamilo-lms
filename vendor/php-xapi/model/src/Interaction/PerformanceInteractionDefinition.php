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
 * An interaction that requires the learner to perform a task that requires
 * multiple steps.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class PerformanceInteractionDefinition extends InteractionDefinition
{
    private $steps;

    /**
     * @param string[]|null               $correctResponsesPattern
     * @param InteractionComponent[]|null $steps
     */
    public function __construct(LanguageMap $name = null, LanguageMap $description = null, IRI $type = null, IRL $moreInfo = null, Extensions $extensions = null, array $correctResponsesPattern = null, array $steps = null)
    {
        parent::__construct($name, $description, $type, $moreInfo, $extensions, $correctResponsesPattern);

        $this->steps = $steps;
    }

    /**
     * @param InteractionComponent[]|null $steps
     */
    public function withSteps(array $steps = null): self
    {
        $interaction = clone $this;
        $interaction->steps = $steps;

        return $interaction;
    }

    /**
     * @return InteractionComponent[]|null
     */
    public function getSteps(): ?array
    {
        return $this->steps;
    }

    public function equals(Definition $definition): bool
    {
        if (!parent::equals($definition)) {
            return false;
        }

        if (!$definition instanceof PerformanceInteractionDefinition) {
            return false;
        }

        if (null !== $this->steps xor null !== $definition->steps) {
            return false;
        }

        if (null !== $this->steps) {
            if (count($this->steps) !== count($definition->steps)) {
                return false;
            }

            foreach ($this->steps as $key => $step) {
                if (!isset($definition->steps[$key])) {
                    return false;
                }

                if (!$step->equals($definition->steps[$key])) {
                    return false;
                }
            }
        }

        return true;
    }
}
