<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces;

use DOMElement;

/**
 * CC Resource Interface.
 */
interface CcIResource
{
    public function getAttrValue(&$nod, $name, $ns = null);

    public function addResource($fname, $location = '');

    public function importResource(DOMElement &$node, CcIManifest &$doc);

    public function processResource($manifestroot, &$fname, $folder);
}
