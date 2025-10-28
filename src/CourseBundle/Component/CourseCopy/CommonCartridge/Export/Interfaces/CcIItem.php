<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces;

/**
 * CC Item Interface.
 */
interface CcIItem
{
    public function addChildItem(self &$item);

    public function attachResource($res);     // can be object or value

    public function hasChildItems();

    public function attrValue(&$nod, $name, $ns = null);

    public function processItem(&$node, &$doc);
}
