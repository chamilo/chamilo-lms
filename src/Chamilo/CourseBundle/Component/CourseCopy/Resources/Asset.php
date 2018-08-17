<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class Asset.
 *
 * @package chamilo.backup
 */
class Asset extends Resource
{
    public $title;
    public $path;
    public $file_type;

    /**
     * Asset constructor.
     *
     * @param int    $id
     * @param int    $path
     * @param string $title
     */
    public function __construct($id, $path, $title)
    {
        parent::__construct($path, RESOURCE_ASSET);
        $this->path = $path;
        $this->title = $title;
    }

    /**
     * Show this document.
     */
    public function show()
    {
        parent::show();
        echo $this->title;
    }
}
