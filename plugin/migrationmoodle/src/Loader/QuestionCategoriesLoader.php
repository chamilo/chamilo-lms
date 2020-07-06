<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class QuestionCategoriesLoader.
 *
 * Loader for create a category for Chamilo quiz questions coming from a Moodle question category.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class QuestionCategoriesLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @throws \Exception
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $course = api_get_course_entity($incomingData['c_id']);

        if (!$course) {
            throw new \Exception("The question category ({$incomingData['name']}) found, but it will not be migrated.");
        }

        $category = new \TestCategory();
        $category->name = $incomingData['name'];
        $category->description = $incomingData['description'];

        $id = $category->save($incomingData['c_id']);

        if (false === $id) {
            throw new \Exception("The quiz category \"{$incomingData['name']}\" already exists.");
        }

        return $id;
    }
}
