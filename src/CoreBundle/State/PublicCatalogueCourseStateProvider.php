<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @implements ProviderInterface<Course>
 */
readonly class PublicCatalogueCourseStateProvider implements ProviderInterface
{
    public function __construct(
        private CourseRepository $courseRepository,
        private SettingsManager $settingsManager,
        private AccessUrlRepository $accessUrlRepository,
        private RequestStack $requestStack,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
        private TokenStorageInterface $tokenStorage
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        $isAuthenticated = \is_object($user);

        // Check if the public catalogue is visible for anonymous users
        if (!$isAuthenticated) {
            $showCatalogue = 'false' !== $this->settingsManager->getSetting('catalog.course_catalog_published', true);
            if (!$showCatalogue) {
                return [];
            }
        }

        $onlyShowMatching = 'true' === $this->settingsManager->getSetting('catalog.only_show_selected_courses', true);
        $onlyShowCoursesWithCategory = $this->settingsManager->getSetting('catalog.only_show_course_from_selected_category', true);

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return [];
        }

        $host = $request->getSchemeAndHttpHost().'/';
        $hidePrivateCourses = 'true' === $this->settingsManager->getSetting('catalog.course_catalog_hide_private', true);
        $visibilities = $hidePrivateCourses
            ? [Course::OPEN_WORLD, Course::OPEN_PLATFORM]
            : [Course::OPEN_WORLD, Course::OPEN_PLATFORM, Course::REGISTERED];

        /** @var AccessUrl $accessUrl */
        $accessUrl = $this->accessUrlRepository->findOneBy(['url' => $host]) ?? $this->accessUrlRepository->find(1);

        // Retrieve all courses visible under this URL
        $courses = $this->courseRepository->createQueryBuilder('c')
            ->innerJoin('c.urls', 'url_rel')
            ->andWhere('url_rel.url = :accessUrl')
            ->andWhere('c.visibility IN (:visibilities)')
            ->setParameter('accessUrl', $accessUrl->getId())
            ->setParameter('visibilities', $visibilities)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        // Global hosting limit (includes all users, not only students)
        $maxUsersPerCourse = (int) $this->settingsManager->getSetting('platform.hosting_limit_users_per_course', true);

        $filteredCourses = [];

        foreach ($courses as $course) {
            if (!$course instanceof Course) {
                continue;
            }

            // Apply "show_in_catalogue" and category filters
            $passesExtraField = true;
            $passesCategory = true;

            if ($onlyShowMatching) {
                $value = $this->extraFieldValuesRepository->getValueByVariableAndItem(
                    'show_in_catalogue',
                    $course->getId(),
                    ExtraField::COURSE_FIELD_TYPE
                );
                $passesExtraField = '1' === $value?->getFieldValue();
            }

            if ($onlyShowCoursesWithCategory) {
                $passesCategory = $course->getCategories()->count() > 0;
            }

            if (!$passesExtraField || !$passesCategory) {
                continue;
            }

            // Compute user subscription info
            if ($isAuthenticated) {
                $course->subscribed = $course->hasSubscriptionByUser($user);
            }

            // Count ALL users in the course (students + teachers + tutors + HR)
            $nbUsers = $course->getUsers()->count();

            // Expose computed fields for API serialization
            $course->nb_students = $nbUsers;
            $course->max_students = $maxUsersPerCourse;
            $course->is_full = $maxUsersPerCourse > 0 && $nbUsers >= $maxUsersPerCourse;

            $filteredCourses[] = $course;
        }

        return $filteredCourses;
    }
}
