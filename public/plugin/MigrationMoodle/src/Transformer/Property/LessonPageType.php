<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class LessonPageType.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LessonPageType implements TransformPropertyInterface
{
    /**
     * @return string
     */
    public function transform(array $data)
    {
        $qtype = current($data);

        switch ($qtype) {
            case 1:
            case 2:
            case 3:
            case 5:
            case 8:
            case 10:
                return 'quiz';
            case 20:
                return 'document';
        }

        return 'dir';
    }
}
