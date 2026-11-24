<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\CourseCopy;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseCopy\CourseCopy;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\CourseCategoryRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

/**
 * @implements ProcessorInterface<CourseCopy, CourseCopy>
 */
final class CourseCopyProcessor implements ProcessorInterface
{
    /**
     * Tools included in a full in-app course copy (same set as the course maintenance tool).
     *
     * @var list<string>
     */
    public const array ALLOWED_TOOLS = [
        'documents',
        'forums',
        'tool_intro',
        'links',
        'quizzes',
        'quiz_questions',
        'assets',
        'surveys',
        'survey_questions',
        'announcements',
        'events',
        'course_descriptions',
        'glossary',
        'wiki',
        'thematic',
        'attendance',
        'works',
        'gradebook',
        'learnpath_category',
        'learnpaths',
    ];

    private const int MAX_COURSE_CODE_LENGTH = 40;
    private const int MAX_COURSE_TITLE_LENGTH = 255;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CourseRepository $courseRepository,
        private readonly CourseCategoryRepository $courseCategoryRepository,
        private readonly LanguageRepository $languageRepository,
        private readonly CourseHelper $courseHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly Security $security,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CourseCopy {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Only platform administrators can copy a course through this endpoint.');
        }
        if (!$data instanceof CourseCopy) {
            throw new BadRequestHttpException('Invalid course copy payload.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new NotFoundHttpException('The current portal was not found.');
        }

        $sourceCode = $this->normalizeCode($data->sourceCode);
        $targetCode = $this->normalizeCode($data->targetCode);
        $targetTitle = $this->normalizeTitle($data->targetTitle);
        $tools = $this->normalizeTools($data->tools);

        $source = $this->resolveExistingCourse($data->sourceId, $sourceCode, $accessUrl, 'Source course');
        $existingTarget = $this->findExistingCourse($data->targetId, $targetCode);

        $targetCategory = null;
        $created = false;
        if ($existingTarget instanceof Course) {
            if (!$this->belongsToAccessUrl($existingTarget, $accessUrl)) {
                throw new NotFoundHttpException('Target course not found.');
            }
            if ($existingTarget->getId() === $source->getId()) {
                throw new UnprocessableEntityHttpException('The target course must be different from the source course.');
            }
            $target = $existingTarget;
        } else {
            if (null !== $data->targetId) {
                throw new NotFoundHttpException('Target course not found.');
            }
            if (null === $targetTitle) {
                throw new UnprocessableEntityHttpException('A title is required to create the target course.');
            }

            $targetCategory = $this->resolveTargetCategory($data->targetCategoryId, $accessUrl);
        }

        // Build the source snapshot before creating the destination to avoid an empty course
        // when source preparation fails. Later restore failures can still leave partial data.
        $sourceInfo = api_get_course_info($source->getCode());
        if (!\is_array($sourceInfo) || '' === (string) ($sourceInfo['code'] ?? '')) {
            throw new NotFoundHttpException('Source course not found.');
        }

        try {
            $builder = new CourseBuilder('partial', $sourceInfo);
            $builder->set_tools_to_build($tools);
            $legacyCourse = $builder->build(0, $source->getCode());
        } catch (Throwable $exception) {
            $this->logger->error('Course copy source preparation failed.', [
                'source_id' => $source->getId(),
                'exception' => $exception,
            ]);

            throw new BadRequestHttpException('The course could not be copied.');
        }

        if (!isset($target)) {
            $params = [
                'title' => $targetTitle,
                'exemplary_content' => false,
            ];
            if (null !== $targetCode) {
                $params['wanted_code'] = $targetCode;
            }
            $languageIso = $this->resolveLanguageIso($data->targetLanguage, $source);
            $params['courseLanguage'] = $languageIso;
            $params['course_language'] = $languageIso;

            try {
                $target = $this->courseHelper->createCourse($params);
            } catch (InvalidArgumentException $exception) {
                throw new UnprocessableEntityHttpException($exception->getMessage());
            }

            if (!$target instanceof Course || null === $target->getId()) {
                throw new BadRequestHttpException('The target course could not be created.');
            }
            $created = true;

            if ($targetCategory instanceof CourseCategory) {
                $target->addCategory($targetCategory);
                $this->entityManager->flush();
            }
        }

        try {
            $restorer = new CourseRestorer($legacyCourse);
            if (!\defined('FILE_RENAME')) {
                \define('FILE_RENAME', 2);
            }
            $restorer->set_file_option(FILE_RENAME);
            $restored = $restorer->restore($target->getCode());
        } catch (Throwable $exception) {
            $this->logger->error('Course copy failed.', [
                'source_id' => $source->getId(),
                'target_id' => $target->getId(),
                'exception' => $exception,
            ]);

            throw new BadRequestHttpException('The course could not be copied.');
        }

        if (false === $restored || [] !== $restorer->getFailedTools()) {
            $this->logger->error('Course copy did not complete all requested tools.', [
                'source_id' => $source->getId(),
                'target_id' => $target->getId(),
                'target_code' => $target->getCode(),
                'failed_tools' => $restorer->getFailedTools(),
            ]);

            throw new BadRequestHttpException('The course could not be copied completely. Check server logs and inspect the target course.');
        }

        $this->entityManager->clear();
        $target = $this->courseRepository->find($target->getId());
        if (!$target instanceof Course) {
            throw new BadRequestHttpException('The course could not be copied.');
        }

        $data->status = 'copied';
        $data->created = $created;
        $data->targetId = $target->getId();
        $data->targetCode = $target->getCode();

        return $data;
    }

    private function resolveTargetCategory(?int $id, AccessUrl $accessUrl): ?CourseCategory
    {
        if (null === $id) {
            return null;
        }
        if ($id <= 0) {
            throw new UnprocessableEntityHttpException('The target category must be a positive identifier.');
        }

        // Include descendants of categories explicitly assigned to the current portal.
        $allowedIds = $this->courseCategoryRepository->findIdsInAccessUrlTree([(int) $accessUrl->getId()]);
        if (!\in_array($id, $allowedIds, true)) {
            throw new UnprocessableEntityHttpException('The target category is not available on this portal.');
        }

        $category = $this->courseCategoryRepository->find($id);
        if (!$category instanceof CourseCategory) {
            throw new UnprocessableEntityHttpException('The target category was not found.');
        }

        return $category;
    }

    /**
     * @param list<string>|null $tools
     *
     * @return list<string>
     */
    private function normalizeTools(?array $tools): array
    {
        if (null === $tools || [] === $tools) {
            return self::ALLOWED_TOOLS;
        }
        if (\count($tools) > \count(self::ALLOWED_TOOLS)) {
            throw new UnprocessableEntityHttpException('Too many tools were requested.');
        }

        $normalized = [];
        foreach ($tools as $tool) {
            if (!\is_string($tool) || !\in_array($tool, self::ALLOWED_TOOLS, true)) {
                throw new UnprocessableEntityHttpException('An unknown or unsupported tool was requested.');
            }
            $normalized[] = $tool;
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeCode(?string $code): ?string
    {
        if (null === $code) {
            return null;
        }
        $code = trim($code);
        if ('' === $code) {
            return null;
        }
        if (\strlen($code) > self::MAX_COURSE_CODE_LENGTH) {
            throw new UnprocessableEntityHttpException('The course code is too long.');
        }
        if (str_contains($code, '/') || str_contains($code, '\\') || str_contains($code, "\0")) {
            throw new UnprocessableEntityHttpException('The course code is invalid.');
        }

        return $code;
    }

    private function normalizeTitle(?string $title): ?string
    {
        if (null === $title) {
            return null;
        }
        $title = trim($title);
        if ('' === $title) {
            return null;
        }
        if (\strlen($title) > self::MAX_COURSE_TITLE_LENGTH) {
            throw new UnprocessableEntityHttpException('The course title is too long.');
        }

        return $title;
    }

    private function resolveExistingCourse(?int $id, ?string $code, AccessUrl $accessUrl, string $label): Course
    {
        if (null === $id && null === $code) {
            throw new UnprocessableEntityHttpException($label.' id or code is required.');
        }
        if (null !== $id && $id <= 0) {
            throw new UnprocessableEntityHttpException($label.' id is invalid.');
        }

        $course = $this->findExistingCourse($id, $code);
        if (!$course instanceof Course || !$this->belongsToAccessUrl($course, $accessUrl)) {
            throw new NotFoundHttpException($label.' not found.');
        }

        return $course;
    }

    private function findExistingCourse(?int $id, ?string $code): ?Course
    {
        if (null !== $id && $id > 0) {
            $byId = $this->courseRepository->find($id);
            if (!$byId instanceof Course) {
                return null;
            }
            if (null !== $code && $byId->getCode() !== $code) {
                throw new UnprocessableEntityHttpException('The course id and code do not refer to the same course.');
            }

            return $byId;
        }
        if (null === $code) {
            return null;
        }

        $byCode = $this->courseRepository->findOneBy(['code' => $code]);

        return $byCode instanceof Course ? $byCode : null;
    }

    /**
     * Accept a full isocode (en_US) or a short prefix (en). When none is given, reuse the source course language.
     */
    private function resolveLanguageIso(?string $requested, Course $source): string
    {
        if (null !== $requested) {
            $requested = trim($requested);
            if ('' === $requested || \strlen($requested) > 20) {
                throw new UnprocessableEntityHttpException('The target language is invalid.');
            }
            $language = $this->languageRepository->findByIsoCode($requested)
                ?? $this->languageRepository->findFirstAvailableByIsoPrefix($requested);
            if (null === $language) {
                throw new UnprocessableEntityHttpException('The target language is invalid.');
            }

            return $language->getIsocode();
        }

        $fromSource = $source->getCourseLanguage();
        if ('' !== $fromSource) {
            return $fromSource;
        }

        $fallback = $this->languageRepository->findByIsoCode('en_US')
            ?? $this->languageRepository->findFirstAvailableByIsoPrefix('en');
        if (null === $fallback) {
            throw new UnprocessableEntityHttpException('The target language is invalid.');
        }

        return $fallback->getIsocode();
    }

    private function belongsToAccessUrl(Course $course, AccessUrl $accessUrl): bool
    {
        $accessUrlId = $accessUrl->getId();
        foreach ($course->getUrls() as $rel) {
            if ($rel->getUrl()?->getId() === $accessUrlId) {
                return true;
            }
        }

        return false;
    }
}
