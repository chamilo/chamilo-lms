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
 * An interaction which asks the learner to select from a discrete set of
 * choices on a scale.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class LikertInteractionDefinition extends InteractionDefinition
{
    private $scale;

    /**
     * @param string[]|null               $correctResponsesPattern
     * @param InteractionComponent[]|null $scale
     */
    public function __construct(LanguageMap $name = null, LanguageMap $description = null, IRI $type = null, IRL $moreInfo = null, Extensions $extensions = null, array $correctResponsesPattern = null, array $scale = null)
    {
        parent::__construct($name, $description, $type, $moreInfo, $extensions, $correctResponsesPattern);

        $this->scale = $scale;
    }

    /**
     * @param InteractionComponent[]|null $scale
     */
    public function withScale(array $scale = null): self
    {
        $interaction = clone $this;
        $interaction->scale = $scale;

        return $interaction;
    }

    /**
     * @return InteractionComponent[]|null
     */
    public function getScale(): ?array
    {
        return $this->scale;
    }

    public function equals(Definition $definition): bool
    {
        if (!parent::equals($definition)) {
            return false;
        }

        if (!$definition instanceof LikertInteractionDefinition) {
            return false;
        }

        if (null !== $this->scale xor null !== $definition->scale) {
            return false;
        }

        if (null !== $this->scale) {
            if (count($this->scale) !== count($definition->scale)) {
                return false;
            }

            foreach ($this->scale as $key => $scale) {
                if (!isset($definition->scale[$key])) {
                    return false;
                }

                if (!$scale->equals($definition->scale[$key])) {
                    return false;
                }
            }
        }

        return true;
    }
}
