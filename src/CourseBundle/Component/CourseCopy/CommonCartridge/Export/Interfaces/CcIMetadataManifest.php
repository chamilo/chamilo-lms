<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces;

/**
 * CC Metadata Manifest Interface.
 */
interface CcIMetadataManifest
{
    public function addMetadataGeneral($obj);

    public function addMetadataTechnical($obj);

    public function addMetadataRights($obj);

    public function addMetadataLifecycle($obj);
}
