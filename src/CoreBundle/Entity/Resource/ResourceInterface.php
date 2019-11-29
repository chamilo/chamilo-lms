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
     */
    public function getResourceIdentifier(): int;

    public function getResourceName(): string;

    public function __toString(): string;
}
