<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseCategoriesTask;

/**
 * Class CourseCategoryParentLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class CourseCategoryLookup extends LoadedKeyLookup
{
    /**
     * CourseCategoryParentLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseCategoriesTask::class;
    }

    /**
     * @throws \Exception
     *
     * @return string|null
     */
    public function transform(array $data)
    {
        $categoryId = parent::transform($data);

        $category = \Database::getManager()->find('ChamiloCoreBundle:CourseCategory', $categoryId);

        if (empty($category)) {
            return null;
        }

        return $category->getCode();
    }
}
