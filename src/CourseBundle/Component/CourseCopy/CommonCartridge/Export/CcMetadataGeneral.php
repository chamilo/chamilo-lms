<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_manifest.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export;

/**
 * Metadata General Type.
 */
class CcMetadataGeneral
{
    public $title = [];
    public $language = [];
    public $description = [];
    public $keyword = [];
    public $coverage = [];
    public $catalog = [];
    public $entry = [];

    public function setCoverage($coverage, $language): void
    {
        $this->coverage[] = [$language, $coverage];
    }

    public function setDescription($description, $language): void
    {
        $this->description[] = [$language, $description];
    }

    public function setKeyword($keyword, $language): void
    {
        $this->keyword[] = [$language, $keyword];
    }

    public function setLanguage($language): void
    {
        $this->language[] = [$language];
    }

    public function setTitle($title, $language): void
    {
        $this->title[] = [$language, $title];
    }

    public function setCatalog($cat): void
    {
        $this->catalog[] = [$cat];
    }

    public function setEntry($entry): void
    {
        $this->entry[] = [$entry];
    }
}
