<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Tool\ToolChain;
use Doctrine\ORM\Event\PrePersistEventArgs;
use RuntimeException;

/**
 * Class CourseListener.
 * Course entity listener, when a course is created/edited and when the tool chain is loaded.
 *
 * @todo check hosting course limits
 */
class CourseListener
{
    public function __construct(
        protected ToolChain $toolChain,
        protected SettingsManager $settingsManager,
    ) {}

    public function prePersist(Course $course, PrePersistEventArgs $args): void
    {
        if (null === $course->getCourseLanguageEntity()) {
            $languageRepository = $args->getObjectManager()->getRepository(Language::class);
            $platformLanguage = $this->settingsManager->getSetting('language.platform_language', true);
            $isoCode = \is_string($platformLanguage) ? trim($platformLanguage) : '';
            $language = '' !== $isoCode ? $languageRepository->findOneBy(['isocode' => $isoCode]) : null;

            if (!$language instanceof Language) {
                $language = $languageRepository->findOneBy(['isocode' => 'en'])
                    ?? $languageRepository->findOneBy(['isocode' => 'en_US']);
            }

            if (!$language instanceof Language) {
                $language = $languageRepository->findOneBy(['available' => true], ['id' => 'ASC'])
                    ?? $languageRepository->findOneBy([], ['id' => 'ASC']);
            }

            if (!$language instanceof Language) {
                throw new RuntimeException('A valid language is required to create a course.');
            }

            $course->setCourseLanguageEntity($language);
        }

        // /$this->checkLimit($repo, $course, $url);
        $this->toolChain->addToolsInCourse($course);
    }

    /*protected function checkLimit(CourseRepository $repo, Course $course, AccessUrl $url): void
     * {
     * $limit = $url->getLimitCourses();
     * if (!empty($limit)) {
     * $count = $repo->getCountCoursesByUrl($url);
     * if ($count >= $limit) {
     * api_warn_hosting_contact('hosting_limit_courses', $limit);
     * throw new \Exception('PortalCoursesLimitReached');
     * }
     * }
     * if (COURSE_VISIBILITY_HIDDEN != $course->getVisibility()) {
     * $limit = $url->getLimitActiveCourses();
     * if (!empty($limit)) {
     * $count = $repo->getCountActiveCoursesByUrl($url);
     * if ($count >= $limit) {
     * api_warn_hosting_contact('hosting_limit_active_courses', $limit);
     * throw new \Exception('PortalActiveCoursesLimitReached');
     * }
     * }
     * }*/
}
