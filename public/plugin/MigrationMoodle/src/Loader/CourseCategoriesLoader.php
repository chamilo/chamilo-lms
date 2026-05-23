<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CourseCategoriesLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseCategoriesLoader implements LoaderInterface
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
        $code = trim((string) $incomingData['code']);
        $title = trim((string) $incomingData['name']);

        if (empty($code)) {
            throw new \Exception('The course category code is empty.');
        }

        if (empty($title)) {
            throw new \Exception('The course category title is empty.');
        }

        $entityManager = \Database::getManager();
        $categoryRepository = $entityManager->getRepository(CourseCategory::class);

        $category = $categoryRepository->findOneBy(['code' => $code]);

        if (!$category instanceof CourseCategory) {
            $category = new CourseCategory();
            $category
                ->setCode($code)
                ->setTitle($title)
                ->setDescription((string) ($incomingData['description'] ?? ''))
                ->setAuthCourseChild('TRUE')
                ->setAuthCatChild('TRUE')
                ->setTreePos($this->getNextTreePosition())
            ;

            $parentCode = trim((string) ($incomingData['parent_id'] ?? ''));

            if (!empty($parentCode)) {
                $parent = $categoryRepository->findOneBy(['code' => $parentCode]);

                if ($parent instanceof CourseCategory) {
                    $category->setParent($parent);
                    $parent->setChildrenCount((int) $parent->getChildrenCount() + 1);
                    $entityManager->persist($parent);
                }
            }

            $entityManager->persist($category);
        }

        $accessUrlId = \MigrationMoodlePlugin::create()->getAccessUrlId();
        $accessUrl = $entityManager->find(AccessUrl::class, $accessUrlId);

        if ($accessUrl instanceof AccessUrl) {
            $category->addUrl($accessUrl);
        }

        $entityManager->persist($category);
        $entityManager->flush();

        $id = $category->getId();

        if (empty($id)) {
            throw new \Exception("Course category ({$code}) not migrated.");
        }

        return $id;
    }

    private function getNextTreePosition()
    {
        $table = \Database::get_main_table(TABLE_MAIN_CATEGORY);
        $row = \Database::select(
            'MAX(tree_pos) AS max_tree_pos',
            $table,
            [],
            'first'
        );

        return (int) ($row['max_tree_pos'] ?? 0) + 1;
    }
}
