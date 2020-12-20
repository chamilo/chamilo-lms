<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

/**
 * Interface ResourceInterface.
 */
interface ResourceInterface
{
    public function __toString(): string;

    /** Returns the resource id identifier. Example for CDocument it will be the value of the field iid.  */
    public function getResourceIdentifier(): int;

    /** Returns the resource name. Example for CDocument it will be the field "title".  */
    public function getResourceName(): string;

    public function setResourceName(string $name);

    public function getResourceNode(): ResourceNode;

    public function setResourceNode(ResourceNode $resourceNode): ResourceInterface;

    public function setParent(AbstractResource $parent);

    //public function addCourseLink(Course $course, Session $session = null, CGroup $group = null, int $visibility);
}
