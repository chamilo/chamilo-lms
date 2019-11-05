<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class CodeLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class CodeLookup implements TransformPropertyInterface
{
    /**
     * @param array $data
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function transform(array $data)
    {
        $name = current($data);

        if (empty($name)) {
            throw new \Exception('The name for the course category is empty.');
        }

        return \CourseManager::generate_course_code($name);
    }
}
