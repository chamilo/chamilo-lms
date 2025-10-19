<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_manifest.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Interfaces\CcIMetadataManifest;
use Exception;

class CcMetadataManifest implements CcIMetadataManifest
{
    public $arraygeneral = [];
    public $arraytech = [];
    public $arrayrights = [];
    public $arraylifecycle = [];

    public function addMetadataGeneral($obj): void
    {
        if (empty($obj)) {
            throw new Exception('Medatada Object given is invalid or null!');
        }
        null !== $obj->title ? $this->arraygeneral['title'] = $obj->title : null;
        null !== $obj->language ? $this->arraygeneral['language'] = $obj->language : null;
        null !== $obj->description ? $this->arraygeneral['description'] = $obj->description : null;
        null !== $obj->keyword ? $this->arraygeneral['keyword'] = $obj->keyword : null;
        null !== $obj->coverage ? $this->arraygeneral['coverage'] = $obj->coverage : null;
        null !== $obj->catalog ? $this->arraygeneral['catalog'] = $obj->catalog : null;
        null !== $obj->entry ? $this->arraygeneral['entry'] = $obj->entry : null;
    }

    public function addMetadataTechnical($obj): void
    {
        if (empty($obj)) {
            throw new Exception('Medatada Object given is invalid or null!');
        }
        null !== $obj->format ? $this->arraytech['format'] = $obj->format : null;
    }

    public function addMetadataRights($obj): void
    {
        if (empty($obj)) {
            throw new Exception('Medatada Object given is invalid or null!');
        }
        null !== $obj->copyright ? $this->arrayrights['copyrightAndOtherRestrictions'] = $obj->copyright : null;
        null !== $obj->description ? $this->arrayrights['description'] = $obj->description : null;
        null !== $obj->cost ? $this->arrayrights['cost'] = $obj->cost : null;
    }

    public function addMetadataLifecycle($obj): void
    {
        if (empty($obj)) {
            throw new Exception('Medatada Object given is invalid or null!');
        }
        null !== $obj->role ? $this->arraylifecycle['role'] = $obj->role : null;
        null !== $obj->entity ? $this->arraylifecycle['entity'] = $obj->entity : null;
        null !== $obj->date ? $this->arraylifecycle['date'] = $obj->date : null;
    }
}
