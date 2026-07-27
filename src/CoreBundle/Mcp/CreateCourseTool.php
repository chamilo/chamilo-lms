<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class CreateCourseTool
{
    public function __construct(
        private Security $security,
        private CourseHelper $courseHelper,
        private EntityManagerInterface $entityManager,
        private LanguageRepository $languageRepository,
        private SettingsManager $settingsManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array{
     *     created: true,
     *     course: array{
     *         course_id: int,
     *         title: string,
     *         code: string,
     *         visual_code: string|null,
     *         language: string,
     *         visibility: int,
     *         url: string
     *     }
     * }
     */
    #[McpTool(
        name: 'create_course',
        description: 'Create a Chamilo course for the authenticated teacher using the platform course-creation rules.',
    )]
    public function createCourse(
        string $title,
        ?string $code = null,
        ?string $language = null,
    ): array {
        $user = $this->security->getUser();

        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('An authenticated Chamilo user is required.');
        }

        if (
            !$this->security->isGranted('ROLE_TEACHER')
            && !$this->security->isGranted('ROLE_ADMIN')
        ) {
            throw new AccessDeniedException('Only teachers and administrators can create courses.');
        }

        $title = trim($title);
        if ('' === $title) {
            throw new InvalidArgumentException('The course title is required.');
        }

        if (mb_strlen($title) > 250) {
            throw new InvalidArgumentException('The course title cannot be longer than 250 characters.');
        }

        $code = null !== $code ? trim($code) : null;
        if ('' === $code) {
            $code = null;
        }

        if (null !== $code && mb_strlen($code) > CourseHelper::MAX_COURSE_LENGTH_CODE) {
            throw new InvalidArgumentException(\sprintf('The course code cannot be longer than %d characters.', CourseHelper::MAX_COURSE_LENGTH_CODE));
        }

        $language = $this->resolveCourseLanguage($language);

        $params = [
            'title' => $title,
            'exemplary_content' => false,
        ];

        if (null !== $code) {
            $params['wanted_code'] = $code;
        }

        $params['course_language'] = $language;

        /** @var Course|null $course */
        $course = $this->entityManager->wrapInTransaction(
            fn (): ?Course => $this->courseHelper->createCourse($params)
        );

        if (!$course instanceof Course || null === $course->getId()) {
            throw new RuntimeException('Chamilo could not create the course.');
        }

        return [
            'created' => true,
            'course' => [
                'course_id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'visual_code' => $course->getVisualCode(),
                'language' => $course->getCourseLanguage(),
                'visibility' => $course->getVisibility(),
                'url' => $this->urlGenerator->generate(
                    'chamilo_core_course_home',
                    ['cid' => $course->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
            ],
        ];
    }

    private function resolveCourseLanguage(?string $requestedLanguage): string
    {
        $requestedLanguage = null !== $requestedLanguage
            ? trim($requestedLanguage)
            : '';

        $usesPlatformDefault = '' === $requestedLanguage;

        if ($usesPlatformDefault) {
            $requestedLanguage = trim((string) $this->settingsManager->getSetting(
                'language.platform_language',
                true,
            ));

            if ('' === $requestedLanguage) {
                $requestedLanguage = trim((string) $this->settingsManager->getSetting(
                    'language.platformLanguage',
                ));
            }
        }

        if ('' === $requestedLanguage) {
            throw new RuntimeException('Chamilo has no valid platform language configured for course creation.');
        }

        if (mb_strlen($requestedLanguage) > 255) {
            throw new InvalidArgumentException('The course language identifier cannot be longer than 255 characters.');
        }

        $normalizedIdentifier = mb_strtolower(str_replace(
            '-',
            '_',
            $requestedLanguage,
        ));

        $language = $this->findLanguageByIdentifier(
            $normalizedIdentifier,
            !$usesPlatformDefault,
        );

        if (null === $language) {
            $language = $this->findLanguageByIsoPrefix($normalizedIdentifier);
        }

        if (null === $language) {
            throw new InvalidArgumentException(\sprintf('The course language "%s" is not available in Chamilo. Use an available ISO code or language name.', $requestedLanguage));
        }

        return $language->getIsocode();
    }

    private function findLanguageByIdentifier(
        string $identifier,
        bool $requireAvailable,
    ): ?Language {
        $queryBuilder = $this->languageRepository->createQueryBuilder('language');

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->orX(
                    'LOWER(language.isocode) = :identifier',
                    'LOWER(language.englishName) = :identifier',
                    'LOWER(language.originalName) = :identifier',
                )
            )
            ->setParameter('identifier', $identifier)
            ->addSelect(
                'CASE WHEN LOWER(language.isocode) = :identifier THEN 0 ELSE 1 END AS HIDDEN identifierPriority'
            )
            ->orderBy('identifierPriority', 'ASC')
            ->addOrderBy('language.isocode', 'ASC')
            ->setMaxResults(1)
        ;

        if ($requireAvailable) {
            $queryBuilder
                ->andWhere('language.available = :available')
                ->setParameter('available', true)
            ;
        }

        $result = $queryBuilder->getQuery()->getResult();

        return $result[0] ?? null;
    }

    private function findLanguageByIsoPrefix(
        string $identifier,
    ): ?Language {
        $baseIso = explode('_', $identifier, 2)[0];

        if (1 !== preg_match('/^[a-z]{2,3}$/', $baseIso)) {
            return null;
        }

        $queryBuilder = $this->languageRepository->createQueryBuilder('language');

        $queryBuilder
            ->andWhere('language.available = :available')
            ->andWhere(
                $queryBuilder->expr()->orX(
                    'LOWER(language.isocode) = :baseIso',
                    'LOWER(language.isocode) LIKE :isoPrefix',
                )
            )
            ->setParameter('available', true)
            ->setParameter('baseIso', $baseIso)
            ->setParameter('isoPrefix', $baseIso.'_%')
            ->addSelect(
                'CASE WHEN LOWER(language.isocode) = :baseIso THEN 0 ELSE 1 END AS HIDDEN isoPriority'
            )
            ->orderBy('isoPriority', 'ASC')
            ->addOrderBy('language.isocode', 'ASC')
            ->setMaxResults(1)
        ;

        $result = $queryBuilder->getQuery()->getResult();

        return $result[0] ?? null;
    }
}
