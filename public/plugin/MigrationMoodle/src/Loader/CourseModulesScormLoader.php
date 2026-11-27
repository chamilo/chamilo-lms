<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ResourceDefaultVisibilityHelper;
use Chamilo\CourseBundle\Entity\CLp;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CourseModulesScormLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseModulesScormLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();
        $course = $em->find(Course::class, (int) $incomingData['c_id']);

        if (!$course) {
            throw new \Exception("Course ({$incomingData['c_id']}) not found.");
        }

        // TODO(schema): createDirectory() wrote the package files under SYS_COURSE_PATH/<course>/scorm, a constant and
        // a storage that no longer exist (SCORM files are now an Asset, see ScormPackageImporter). Only the path
        // is computed here, the files of the Moodle package are not copied: product decision pending.
        $path = self::generateDirectoryName($incomingData['name']).'/.';

        // The learning path belongs to the course through its resource node and link (c_lp has no c_id,
        // display_order, session_id nor item_property any more); the link has no session, like before.
        $lp = (new CLp())
            ->setLpType(CLp::SCORM_TYPE)
            ->setTitle($incomingData['name'])
            ->setRef((string) ($incomingData['ref'] ?? ''))
            ->setPath($path)
            ->setDescription('')
            ->setForceCommit(false)
            ->setDefaultViewMod('embedded')
            ->setDefaultEncoding('UTF-8')
            ->setJsLib('scorm_api.php')
            ->setContentMaker('')
            ->setContentLicense('')
            ->setDebug(false)
            ->setTheme('')
            ->setAuthor('')
            ->setPrerequisite(0)
            ->setSeriousgameMode(false)
            ->setAutolaunch(0)
            ->setMaxAttempts(0)
            ->setSubscribeUsers(0)
            ->setUseMaxScore((int) (100 == $incomingData['use_max_score']))
            ->setHideTocFrame((bool) ($incomingData['hide_toc_frame'] ?? false))
            ->setCreatedOn($incomingData['created_on'] ?: new \DateTime())
            ->setModifiedOn($incomingData['modified_on'] ?: new \DateTime())
            ->setPublishedOn($incomingData['publicated_on'] ?: null)
            // The CLI has no logged in user: the admin (id 1) is the creator, like the former item_property rows.
            ->setCreator($em->getReference(User::class, 1))
            ->setParent($course)
            ->addCourseLink(
                $course,
                null,
                visibility: Container::$container->get(ResourceDefaultVisibilityHelper::class)
                    ->getResourceLinkVisibility(ResourceDefaultVisibilityHelper::LEARNING_PATH, $course)
            )
        ;

        Container::getLpRepository()->createLp($lp);

        $lpId = $lp->getIid();

        return $lpId;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public static function generateDirectoryName($fileName)
    {
        $newDirectory = trim($fileName);
        $newDirectory = trim($newDirectory, '/');

        return api_replace_dangerous_char($newDirectory);
    }

    /**
     * @param string $name
     * @param string $courseCode
     *
     * @return string
     */
    private function createDirectory($name, $courseCode)
    {
        $courseRelDir = api_get_path(SYS_COURSE_PATH).api_get_course_path($courseCode).'/scorm';

        $newDirectory = self::generateDirectoryName($name);

        $fullPath = "$courseRelDir/$newDirectory";

        $fileSystem = new Filesystem();

        if (!is_dir($fullPath)) {
            $fileSystem->mkdir(
                $fullPath,
                api_get_permissions_for_new_directories()
            );
        }

        return "$newDirectory/.";
    }
}
