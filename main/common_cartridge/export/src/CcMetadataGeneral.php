<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_manifest.php under GNU/GPL license */

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

    public function setCoverage($coverage, $language)
    {
        $this->coverage[] = [$language, $coverage];
    }

    public function setDescription($description, $language)
    {
        $this->description[] = [$language, $description];
    }

    public function setKeyword($keyword, $language)
    {
        $this->keyword[] = [$language, $keyword];
    }

    public function setLanguage($language)
    {
        $this->language[] = [$language];
    }

    public function setTitle($title, $language)
    {
        $this->title[] = [$language, $title];
    }

    public function setCatalog($cat)
    {
        $this->catalog[] = [$cat];
    }

    public function setEntry($entry)
    {
        $this->entry[] = [$entry];
    }
}
