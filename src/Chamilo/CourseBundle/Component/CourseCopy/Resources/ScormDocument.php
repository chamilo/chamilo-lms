<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * ScormDocument class.
 *
 * @author Olivier Brouckaert <oli.brouckaert@dokeos.com>
 *
 * @package chamilo.backup
 */
class ScormDocument extends Resource
{
    public $path;
    public $title;

    /**
     * Create a new Scorm Document.
     *
     * @param int    $id
     * @param string $path
     * @param string $title
     */
    public function __construct($id, $path, $title)
    {
        parent::__construct($id, RESOURCE_SCORM);
        $this->path = 'scorm'.$path;
        $this->title = $title;
    }

    /**
     * Show this document.
     */
    public function show()
    {
        parent::show();
        $path = preg_replace('@^scorm/@', '', $this->path);
        echo $path;
        if (!empty($this->title)) {
            if (strpos($path, $this->title) === false) {
                echo " - ".$this->title;
            }
        }
    }
}
