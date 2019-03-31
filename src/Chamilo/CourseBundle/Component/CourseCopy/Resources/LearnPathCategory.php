<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

use Chamilo\CourseBundle\Entity\CLpCategory;

/**
 * Class LearnPathCategory.
 *
 * @package Chamilo\CourseBundle\Component\CourseCopy\Resources
 */
class LearnPathCategory extends Resource
{
    /**
     * @var CLpCategory
     */
    public $object;

    /**
     * @param int    $id
     * @param string $object
     */
    public function __construct($id, $object)
    {
        parent::__construct($id, RESOURCE_LEARNPATH_CATEGORY);
        $this->object = $object;
    }

    /**
     * Show this resource.
     */
    public function show()
    {
        parent::show();
        echo $this->object->getName();
    }
}
