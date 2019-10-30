<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

/**
 * Interface ResourceInterface.
 */
interface ResourceInterface
{
    /**
     * Returns the resource id.
     *
     * @return int
     */
    public function getResourceIdentifier(): int;

    /**
     * @return string
     */
    public function getResourceName(): string;

    /**
     * @return string
     */
    public function getToolName(): string;
}
