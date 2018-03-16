<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class Document.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class Document extends Resource
{
    public $path;
    public $comment;
    public $file_type;
    public $size;
    public $title;

    /**
     * Create a new Document.
     *
     * @param int    $id
     * @param string $path
     * @param string $comment
     * @param string $title
     * @param string $file_type (DOCUMENT or FOLDER);
     * @param int    $size
     */
    public function __construct($id, $path, $comment, $title, $file_type, $size)
    {
        parent::__construct($id, RESOURCE_DOCUMENT);
        $this->path = 'document'.$path;
        $this->comment = $comment;
        $this->title = $title;
        $this->file_type = $file_type;
        $this->size = $size;
    }

    /**
     * Show this document.
     */
    public function show()
    {
        parent::show();
        echo preg_replace('@^document@', '', $this->path);
        if (!empty($this->title)) {
            if (strpos($this->path, $this->title) === false) {
                echo " - ".$this->title;
            }
        }
    }
}
