<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class CourseVisibility.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class CourseVisibility implements TransformPropertyInterface
{
    /**
     * @return int
     */
    public function transform(array $data)
    {
        $visible = (bool) current($data);

        if ($visible) {
            return Course::REGISTERED;
        }

        return Course::HIDDEN;
    }
}
