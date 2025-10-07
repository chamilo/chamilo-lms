<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

class Document extends Resource
{
    public string $path;
    public ?string $comment = null;
    public string $file_type;
    public string $size;
    public string $title;

    public function __construct($id, $fullPath, $comment, $title, $file_type, $size)
    {
        parent::__construct($id, RESOURCE_DOCUMENT);
        $clean         = ltrim((string)$fullPath, '/');
        $this->path      = 'document/'.$clean;
        $this->comment   = $comment ?? '';
        $this->title     = (string)$title;
        $this->file_type = (string)$file_type;
        $this->size      = (string)$size;
    }

    public function show()
    {
        parent::show();
        echo preg_replace('@^document@', '', $this->path);
        if (!empty($this->title) && false === strpos($this->path, $this->title)) {
            echo ' - '.$this->title;
        }
    }
}
