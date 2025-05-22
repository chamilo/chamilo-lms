<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|null|object
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        $isAuthenticated = \is_object($user);

        if (!$isAuthenticated) {
            $showCatalogue = $this->settingsManager->getSetting('course.course_catalog_published', true) !== 'false';
            if (!$showCatalogue) {
                return [];
            }
        }

        $onlyShowMatching = $this->settingsManager->getSetting('course.show_courses_in_catalogue', true) === 'true';
        $onlyShowCoursesWithCategory = $this->settingsManager->getSetting('course.courses_catalogue_show_only_category', true) === 'true';

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return [];
        }

        $host = $request->getSchemeAndHttpHost() . '/';
        $accessUrl = $this->accessUrlRepository->findOneBy(['url' => $host]) ?? $this->accessUrlRepository->find(1);
        $courses = $this->courseRepository->createQueryBuilder('c')
            ->innerJoin('c.urls', 'url_rel')
            ->andWhere('url_rel.url = :accessUrl')
            ->andWhere('c.visibility IN (:visibilities)')
            ->setParameter('accessUrl', $accessUrl)
            ->setParameter('visibilities', [Course::OPEN_WORLD, Course::OPEN_PLATFORM])
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();

        if (!$onlyShowMatching && !$onlyShowCoursesWithCategory) {
            return $courses;
        }

        $filtered = [];
        foreach ($courses as $course) {
            $passesExtraField = true;
            $passesCategory = true;

            if ($onlyShowMatching) {
                $value = $this->extraFieldValuesRepository->getValueByVariableAndItem(
                    'show_in_catalogue',
                    $course->getId(),
                    ExtraField::COURSE_FIELD_TYPE
                );
                $passesExtraField = $value?->getFieldValue() === '1';
            }

            if ($onlyShowCoursesWithCategory) {
                $passesCategory = $course->getCategories()->count() > 0;
            }

            if ($passesExtraField && $passesCategory) {
                $filtered[] = $course;
            }
        }

        return $filtered;
    }
}
