<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

/**
 * Interface ResourceInterface.
 *
 * @package Chamilo\CoreBundle\Entity\Resource
 */
interface ResourceInterface
{
    public function setResourceNode(ResourceNode $resourceNode);

    public function getResourceNode();

    /**
     * Returns the resource id.
     *
     * @return int
     */
    public function getResourceIdentifier(): int;

    public function getResourceName(): string;

    public function getToolName(): string;

    //"getName()", "name()", "isName()", "hasName()", "__get()"
}
