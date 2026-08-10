<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseDescription;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Service\Html\TranslateHtmlLanguageService;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

/**
 * Shared lookup + create/edit/delete mechanics for the MCP course description
 * tools. Mirrors the base-course-only scoping already used by every other
 * MCP content tool (no session support), and the "one item per standard
 * type, several allowed for custom/Other" rule already enforced by
 * CourseDescriptionItemProcessor for the Vue-facing API.
 *
 * The guiding-question text below intentionally mirrors
 * CourseDescriptionItemProvider's private TYPE_LABELS/HELP_TEXTS/
 * INFORMATION_TEXTS constants (English only, like every other MCP-facing
 * string in this codebase) so the AI client asks the same questions the
 * legacy/Vue "Course Description" edit form already prompts a human with.
 */
final readonly class CourseDescriptionContentService
{
    private const int MAX_CONTENT_LENGTH = 2_000_000;

    /**
     * @var array<int, string>
     */
    private const array TYPE_LABELS = [
        CCourseDescription::TYPE_DESCRIPTION => 'Description',
        CCourseDescription::TYPE_OBJECTIVES => 'Objectives',
        CCourseDescription::TYPE_TOPICS => 'Topics',
        CCourseDescription::TYPE_METHODOLOGY => 'Methodology',
        CCourseDescription::TYPE_COURSE_MATERIAL => 'Course material',
        CCourseDescription::TYPE_RESOURCES => 'Resources',
        CCourseDescription::TYPE_ASSESSMENT => 'Assessment',
        CCourseDescription::TYPE_CUSTOM => 'Other',
    ];

    /**
     * @var array<int, string>
     */
    private const array GUIDING_QUESTIONS = [
        CCourseDescription::TYPE_DESCRIPTION => 'What is the goal of the course? Are there prerequisites? How is this training connected to other courses?',
        CCourseDescription::TYPE_OBJECTIVES => 'What should the end results be when the learner has completed the course? What are the activities performed during the course?',
        CCourseDescription::TYPE_TOPICS => 'How does the course progress? Where should the learner pay special care? Are there identifiable problems in understanding different areas? How much time should one dedicate to the different areas of the course?',
        CCourseDescription::TYPE_METHODOLOGY => 'What methods and activities help achieve the objectives of the course? What would the schedule be?',
        CCourseDescription::TYPE_COURSE_MATERIAL => 'Is there a course book, a collection of papers, a bibliography, a list of links on the internet?',
        CCourseDescription::TYPE_RESOURCES => 'Consider the courses, tutors, a technical helpdesk, teachers, and/or materials available.',
        CCourseDescription::TYPE_ASSESSMENT => 'How will learners be assessed? Are there strategies to develop in order to master the topic?',
    ];

    /**
     * @var array<int, string>
     */
    private const array INFORMATION_TEXTS = [
        CCourseDescription::TYPE_DESCRIPTION => 'Describe the course (number of hours, serial number, location) and teacher (name, office, Tel., e-mail, office hours...).',
        CCourseDescription::TYPE_OBJECTIVES => 'What are the objectives of the course (competences, skills, outcomes)?',
        CCourseDescription::TYPE_TOPICS => 'List of topics included in the training. Importance of each topic. Level of difficulty. Structure and inter-dependence of the different parts.',
        CCourseDescription::TYPE_METHODOLOGY => 'Presentation of the activities (conference, papers, group research, labs...).',
        CCourseDescription::TYPE_COURSE_MATERIAL => 'Short description of the course materials.',
        CCourseDescription::TYPE_RESOURCES => 'Describe the available courses, tutors, technical helpdesk, teachers, and/or materials.',
        CCourseDescription::TYPE_ASSESSMENT => 'Criteria for skills acquisition.',
    ];

    public function __construct(
        private CCourseDescriptionRepository $courseDescriptionRepository,
        private EntityManagerInterface $entityManager,
        private LanguageRepository $languageRepository,
        private CourseDocumentContentService $documentContentService,
        private TranslateHtmlLanguageService $translateHtmlLanguageService,
    ) {}

    /**
     * @return array{
     *     course_id: int,
     *     custom_type: int,
     *     sections: list<array<string, mixed>>,
     *     custom_items: list<array<string, mixed>>
     * }
     */
    public function getTemplate(Course $course): array
    {
        /** @var array<int, CCourseDescription> $existingByType */
        $existingByType = [];
        $customItems = [];

        foreach ($this->courseDescriptionRepository->findAllInCourse($course) as $description) {
            if (!$description instanceof CCourseDescription || !$this->belongsToBaseCourse($description, $course)) {
                continue;
            }

            if (CCourseDescription::TYPE_CUSTOM === $description->getDescriptionType()) {
                $customItems[] = $this->normalize($description);

                continue;
            }

            $existingByType[$description->getDescriptionType()] ??= $description;
        }

        $sections = [];
        foreach (self::TYPE_LABELS as $type => $label) {
            if (CCourseDescription::TYPE_CUSTOM === $type) {
                continue;
            }

            $existing = $existingByType[$type] ?? null;
            $sections[] = [
                'description_type' => $type,
                'label' => $label,
                'guiding_question' => self::GUIDING_QUESTIONS[$type] ?? '',
                'information' => self::INFORMATION_TEXTS[$type] ?? '',
                'exists' => $existing instanceof CCourseDescription,
                'description_id' => $existing?->getIid(),
                'title' => $existing instanceof CCourseDescription ? (string) $existing->getTitle() : null,
                'word_count' => $existing instanceof CCourseDescription ? $this->countWords((string) $existing->getContent()) : null,
                'language' => $existing instanceof CCourseDescription ? $this->resourceLanguageIsoCode($existing) : null,
            ];
        }

        return [
            'course_id' => (int) $course->getId(),
            'custom_type' => CCourseDescription::TYPE_CUSTOM,
            'sections' => $sections,
            'custom_items' => $customItems,
        ];
    }

    /**
     * Returns course description items. When neither descriptionId nor
     * descriptionType is given, every base-course item is returned (standard
     * sections ordered 1-7, then custom "Other" items). With a filter, returns
     * the single matching item (custom/"Other" items require descriptionId).
     *
     * Modes:
     * - full (default): full HTML body (legacy behaviour)
     * - inventory: language inventory only (no HTML body) — cheap for translation planning
     * - source: source-language HTML only + inventory — enough to translate without the multi-lang blob
     *
     * @return array{
     *     course_id: int,
     *     total: int,
     *     mode: string,
     *     items: list<array<string, mixed>>
     * }
     */
    public function read(
        Course $course,
        ?int $descriptionId = null,
        ?int $descriptionType = null,
        string $mode = TranslateHtmlLanguageService::READ_MODE_FULL,
        ?string $sourceLanguage = null,
    ): array {
        $mode = $this->translateHtmlLanguageService->assertReadMode($mode);
        $sourceLanguage = $this->resolveSourceLanguageIsoCode($course, $sourceLanguage);
        $descriptionId = (null !== $descriptionId && $descriptionId > 0) ? $descriptionId : null;
        $descriptionType = (null !== $descriptionType && $descriptionType > 0) ? $descriptionType : null;

        if (null !== $descriptionId || null !== $descriptionType) {
            $description = $this->resolveByIdOrType($course, $descriptionId, $descriptionType);
            $items = [$this->normalizeForRead($description, $mode, $sourceLanguage)];

            return [
                'course_id' => (int) $course->getId(),
                'total' => 1,
                'mode' => $mode,
                'items' => $items,
            ];
        }

        /** @var array<int, CCourseDescription> $standardByType */
        $standardByType = [];
        $customItems = [];

        foreach ($this->courseDescriptionRepository->findAllInCourse($course) as $description) {
            if (!$description instanceof CCourseDescription || !$this->belongsToBaseCourse($description, $course)) {
                continue;
            }

            if (CCourseDescription::TYPE_CUSTOM === $description->getDescriptionType()) {
                $customItems[] = $description;

                continue;
            }

            $standardByType[$description->getDescriptionType()] ??= $description;
        }

        $items = [];
        foreach (array_keys(self::TYPE_LABELS) as $type) {
            if (CCourseDescription::TYPE_CUSTOM === $type) {
                continue;
            }

            $existing = $standardByType[$type] ?? null;
            if ($existing instanceof CCourseDescription) {
                $items[] = $this->normalizeForRead($existing, $mode, $sourceLanguage);
            }
        }

        foreach ($customItems as $custom) {
            $items[] = $this->normalizeForRead($custom, $mode, $sourceLanguage);
        }

        return [
            'course_id' => (int) $course->getId(),
            'total' => \count($items),
            'mode' => $mode,
            'items' => $items,
        ];
    }

    /**
     * Upsert a single translatehtml language variant on a course description
     * without requiring the client to resend the full multi-language HTML.
     *
     * @return array{
     *     updated: true,
     *     action: 'created'|'replaced',
     *     language: string,
     *     present_languages: list<string>,
     *     content_sha256: string,
     *     chars: int,
     *     words: int
     * }&array<string, mixed>
     */
    public function upsertLanguage(
        Course $course,
        ?int $descriptionId,
        ?int $descriptionType,
        string $language,
        string $content,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        $description = $this->resolveByIdOrType($course, $descriptionId, $descriptionType);
        $languageIso = $this->resolveOptionalLanguageIsoCode($language);
        if (null === $languageIso) {
            throw new InvalidArgumentException('The language is required.');
        }

        $sourceLanguageIso = $this->resolveSourceLanguageIsoCode($course, $sourceLanguage);
        $currentHtml = (string) $description->getContent();

        $result = $this->translateHtmlLanguageService->upsertLanguageSanitized(
            $currentHtml,
            $languageIso,
            $content,
            $mode,
            $sourceLanguageIso,
            $ifMatchSha256,
            fn (string $html): string => $this->assertValidContent($html),
        );

        $description->setContent($result['html']);
        $this->courseDescriptionRepository->update($description);

        $normalized = $this->normalize($description);
        // Keep response light: inventory metadata, not the full multi-lang body.
        unset($normalized['content']);
        // Resource-node "language" is a single ISO on the node — keep it under a distinct key
        // so it does not overwrite the upserted translatehtml language code.
        $normalized['resource_language'] = $normalized['language'] ?? null;
        unset($normalized['language']);

        return [
            ...$normalized,
            'updated' => true,
            'action' => $result['action'],
            'language' => $result['language'],
            'present_languages' => $result['present_languages'],
            'content_sha256' => $result['content_sha256'],
            'chars' => $result['chars'],
            'words' => $result['words'],
            'has_markers' => $result['has_markers'],
            'per_language' => $result['per_language'],
        ];
    }

    /**
     * Creates a new item, or — for the 7 standard section types — updates
     * the existing one in place so repeated calls stay idempotent (a course
     * only ever has one Description, one Objectives item, etc., matching
     * CourseDescriptionItemProcessor's create-or-reuse-by-type rule). Type 8
     * (custom/"Other") always creates a new item since a course may have
     * several.
     *
     * @return array{created: bool, updated_existing: bool}&array<string, mixed>
     */
    public function createOrUpdate(
        Course $course,
        int $descriptionType,
        string $title,
        string $content,
        ?string $language,
    ): array {
        if (!\in_array($descriptionType, CCourseDescription::getTypes(), true)) {
            throw new InvalidArgumentException('The course description type is invalid.');
        }

        $title = $this->assertValidTitle($title);
        $content = $this->assertValidContent($content);
        $languageIsoCode = $this->resolveOptionalLanguageIsoCode($language);

        $existing = CCourseDescription::TYPE_CUSTOM !== $descriptionType
            ? $this->findOwnDescriptionByType($descriptionType, $course)
            : null;

        if ($existing instanceof CCourseDescription) {
            $existing->setTitle($title)->setContent($content);
            $this->applyLanguage($existing, $languageIsoCode);
            $this->courseDescriptionRepository->update($existing);

            return ['created' => false, 'updated_existing' => true, ...$this->normalize($existing)];
        }

        $description = new CCourseDescription();
        $description
            ->setParent($course)
            ->addCourseLink($course)
            ->setDescriptionType($descriptionType)
            ->setTitle($title)
            ->setContent($content)
        ;

        $this->courseDescriptionRepository->create($description);
        $this->applyLanguage($description, $languageIsoCode);
        $this->entityManager->flush();

        return ['created' => true, 'updated_existing' => false, ...$this->normalize($description)];
    }

    /**
     * @return array{updated: true, changed_fields: list<string>}&array<string, mixed>
     */
    public function edit(
        Course $course,
        ?int $descriptionId,
        ?int $descriptionType,
        ?string $content,
        ?string $newTitle,
        ?string $language,
    ): array {
        $description = $this->resolveByIdOrType($course, $descriptionId, $descriptionType);
        $changedFields = [];

        $requestedNewTitle = null !== $newTitle ? trim(strip_tags($newTitle)) : null;
        if (null !== $requestedNewTitle) {
            if ('' === $requestedNewTitle) {
                throw new InvalidArgumentException('The new title cannot be empty.');
            }
            if (mb_strlen($requestedNewTitle) > 250) {
                throw new InvalidArgumentException('The new title cannot be longer than 250 characters.');
            }
            if ($requestedNewTitle !== $description->getTitle()) {
                $description->setTitle($requestedNewTitle);
                $changedFields[] = 'title';
            }
        }

        if (null !== $content) {
            $newContent = $this->assertValidContent($content);
            if ($newContent !== (string) $description->getContent()) {
                $description->setContent($newContent);
                $changedFields[] = 'content';
            }
        }

        if (null !== $language) {
            $languageIsoCode = $this->resolveOptionalLanguageIsoCode($language);
            if ($languageIsoCode !== $this->resourceLanguageIsoCode($description)) {
                $this->applyLanguage($description, $languageIsoCode);
                $changedFields[] = 'language';
            }
        }

        if ([] === $changedFields) {
            throw new InvalidArgumentException('No change was provided. Supply content, newTitle and/or language.');
        }

        $this->courseDescriptionRepository->update($description);

        return ['updated' => true, 'changed_fields' => $changedFields, ...$this->normalize($description)];
    }

    /**
     * @return array{deleted: true}&array<string, mixed>
     */
    public function delete(Course $course, ?int $descriptionId, ?int $descriptionType): array
    {
        $description = $this->resolveByIdOrType($course, $descriptionId, $descriptionType);
        $normalized = $this->normalize($description);
        $this->courseDescriptionRepository->delete($description);

        return ['deleted' => true, ...$normalized];
    }

    private function assertValidTitle(string $title): string
    {
        $title = trim(strip_tags($title));
        if ('' === $title) {
            throw new InvalidArgumentException('The course description title is required.');
        }
        if (mb_strlen($title) > 250) {
            throw new InvalidArgumentException('The course description title cannot be longer than 250 characters.');
        }

        return $title;
    }

    private function assertValidContent(string $content): string
    {
        $content = trim($content);
        if ('' === $content) {
            throw new InvalidArgumentException('The course description content is required.');
        }
        if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
            throw new InvalidArgumentException('The course description content is too large.');
        }

        $content = $this->documentContentService->sanitizeHtml($content);
        if ('' === trim(strip_tags($content))) {
            throw new InvalidArgumentException('The course description content is empty after sanitization.');
        }

        return $content;
    }

    private function findOwnDescriptionByType(int $descriptionType, Course $course): ?CCourseDescription
    {
        foreach ($this->courseDescriptionRepository->findByTypeInCourse($descriptionType, $course) as $candidate) {
            if ($candidate instanceof CCourseDescription && $this->belongsToBaseCourse($candidate, $course)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveByIdOrType(Course $course, ?int $descriptionId, ?int $descriptionType): CCourseDescription
    {
        $descriptionId = (null !== $descriptionId && $descriptionId > 0) ? $descriptionId : null;
        $descriptionType = (null !== $descriptionType && $descriptionType > 0) ? $descriptionType : null;

        if (null === $descriptionId && null === $descriptionType) {
            throw new InvalidArgumentException('Provide either descriptionId or descriptionType.');
        }

        if (null !== $descriptionId) {
            $description = $this->courseDescriptionRepository->find($descriptionId);
            if (!$description instanceof CCourseDescription || !$this->belongsToBaseCourse($description, $course)) {
                throw new InvalidArgumentException('The course description was not found in this course.');
            }

            return $description;
        }

        if (CCourseDescription::TYPE_CUSTOM === $descriptionType) {
            throw new InvalidArgumentException('Provide descriptionId to target a custom ("Other") course description item, since a course can have several.');
        }

        if (!\in_array($descriptionType, CCourseDescription::getTypes(), true)) {
            throw new InvalidArgumentException('The course description type is invalid.');
        }

        $description = $this->findOwnDescriptionByType($descriptionType, $course);
        if (!$description instanceof CCourseDescription) {
            throw new InvalidArgumentException(\sprintf('No "%s" course description item exists yet in this course.', $this->typeLabel($descriptionType)));
        }

        return $description;
    }

    private function belongsToBaseCourse(CCourseDescription $description, Course $course): bool
    {
        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            $linkCourse = $link->getCourse();
            if (null !== $linkCourse && $linkCourse->getId() === $course->getId() && null === $link->getSession()) {
                return true;
            }
        }

        return false;
    }

    private function applyLanguage(CCourseDescription $description, ?string $languageIsoCode): void
    {
        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode) {
            return;
        }

        $language = null;
        if (null !== $languageIsoCode) {
            $language = $this->entityManager->getRepository(Language::class)->findOneBy([
                'isocode' => $languageIsoCode,
                'available' => true,
            ]);
        }

        $resourceNode->setLanguage($language instanceof Language ? $language : null);
        $this->entityManager->persist($resourceNode);
    }

    private function resolveOptionalLanguageIsoCode(?string $language): ?string
    {
        $language = null !== $language ? trim($language) : '';
        if ('' === $language) {
            return null;
        }

        $resolved = $this->languageRepository->findOneAvailableByTitleOrCode($language);
        if (!$resolved instanceof Language) {
            throw new InvalidArgumentException(\sprintf('Unknown language "%s". Provide a language name (e.g. "Spanish") or an existing Chamilo language code (e.g. "es").', $language));
        }

        return $resolved->getIsocode();
    }

    /**
     * Prefer explicit argument, then resource language, then course language, then platform default.
     */
    private function resolveSourceLanguageIsoCode(Course $course, ?string $sourceLanguage): string
    {
        if (null !== $sourceLanguage && '' !== trim($sourceLanguage)) {
            $resolved = $this->resolveOptionalLanguageIsoCode($sourceLanguage);
            if (null !== $resolved) {
                return $this->translateHtmlLanguageService->normalizeLanguageCode($resolved);
            }
        }

        $courseLanguage = trim((string) $course->getCourseLanguage());
        if ('' !== $courseLanguage) {
            $fromCourse = $this->languageRepository->findOneAvailableByTitleOrCode($courseLanguage);
            if ($fromCourse instanceof Language) {
                return $this->translateHtmlLanguageService->normalizeLanguageCode((string) $fromCourse->getIsocode());
            }

            return $this->translateHtmlLanguageService->normalizeLanguageCode($courseLanguage);
        }

        $platformDefault = $this->languageRepository->getPlatformDefaultIso();

        return $this->translateHtmlLanguageService->normalizeLanguageCode($platformDefault ?: 'en');
    }

    private function resourceLanguageIsoCode(CCourseDescription $description): ?string
    {
        $language = $description->getResourceNode()?->getLanguage();

        return $language instanceof Language ? $language->getIsocode() : null;
    }

    private function typeLabel(int $type): string
    {
        return self::TYPE_LABELS[$type] ?? self::TYPE_LABELS[CCourseDescription::TYPE_CUSTOM];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(CCourseDescription $description): array
    {
        return [
            'description_id' => (int) $description->getIid(),
            'description_type' => (int) $description->getDescriptionType(),
            'type_label' => $this->typeLabel((int) $description->getDescriptionType()),
            'title' => (string) $description->getTitle(),
            'content' => (string) $description->getContent(),
            'word_count' => $this->countWords((string) $description->getContent()),
            'language' => $this->resourceLanguageIsoCode($description),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeForRead(CCourseDescription $description, string $mode, string $sourceLanguage): array
    {
        return [
            'description_id' => (int) $description->getIid(),
            'description_type' => (int) $description->getDescriptionType(),
            'type_label' => $this->typeLabel((int) $description->getDescriptionType()),
            'title' => (string) $description->getTitle(),
            'language' => $this->resourceLanguageIsoCode($description),
            ...$this->translateHtmlLanguageService->projectHtmlField(
                (string) $description->getContent(),
                $mode,
                $sourceLanguage,
                'content',
            ),
        ];
    }

    private function countWords(string $html): int
    {
        return $this->translateHtmlLanguageService->countWords($html);
    }
}
