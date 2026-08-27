<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\StudentSuccess;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;

use const DATE_ATOM;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final readonly class StudentSuccessAnalysisStorage
{
    public const string COURSE_ANALYSIS_VARIABLE = 'student_success_course_analysis';
    public const string USER_ANALYSIS_VARIABLE = 'student_success_ai_coach';

    private const int STORAGE_VERSION = 1;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
    ) {}

    /**
     * @param array<string, mixed> $analysis
     * @param array<string, mixed> $metadata
     *
     * @throws JsonException
     */
    public function storeCourseAnalysis(
        Course $course,
        ?Session $session,
        array $analysis,
        array $metadata = [],
    ): void {
        $field = $this->getOrCreateField(
            ExtraField::COURSE_FIELD_TYPE,
            self::COURSE_ANALYSIS_VARIABLE,
            'Student Success AI course analysis',
        );

        $stored = $this->readStorage(
            self::COURSE_ANALYSIS_VARIABLE,
            (int) $course->getId(),
            ExtraField::COURSE_FIELD_TYPE,
        );
        $contexts = $this->readContexts($stored);
        $sessionId = (int) ($session?->getId() ?? 0);

        $contexts[$this->courseContextKey($sessionId)] = [
            'version' => self::STORAGE_VERSION,
            'courseId' => (int) $course->getId(),
            'sessionId' => $sessionId,
            'generatedAt' => $this->now(),
            'analysis' => $analysis,
            'metadata' => $metadata,
        ];

        $this->extraFieldValuesRepository->updateItemData(
            $field,
            $course,
            $this->encodeStorage($contexts),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCourseAnalysis(Course $course, ?Session $session): ?array
    {
        $stored = $this->readStorage(
            self::COURSE_ANALYSIS_VARIABLE,
            (int) $course->getId(),
            ExtraField::COURSE_FIELD_TYPE,
        );
        $contexts = $this->readContexts($stored);
        $sessionId = (int) ($session?->getId() ?? 0);
        $context = $contexts[$this->courseContextKey($sessionId)] ?? null;

        return \is_array($context) ? $context : null;
    }

    public function hasCourseAnalysis(Course $course, ?Session $session): bool
    {
        $context = $this->getCourseAnalysis($course, $session);

        return \is_array($context['analysis'] ?? null) && [] !== $context['analysis'];
    }

    /**
     * @param array<string, mixed> $analysis
     * @param array<string, mixed> $metadata
     *
     * @throws JsonException
     */
    public function storeStudentAnalysis(
        User $user,
        Course $course,
        ?Session $session,
        array $analysis,
        array $metadata = [],
    ): void {
        $field = $this->getOrCreateField(
            ExtraField::USER_FIELD_TYPE,
            self::USER_ANALYSIS_VARIABLE,
            'Student Success AI Coach',
        );

        $stored = $this->readStorage(
            self::USER_ANALYSIS_VARIABLE,
            (int) $user->getId(),
            ExtraField::USER_FIELD_TYPE,
        );
        $contexts = $this->readContexts($stored);
        $sessionId = (int) ($session?->getId() ?? 0);

        $contexts[$this->studentContextKey((int) $course->getId(), $sessionId)] = [
            'version' => self::STORAGE_VERSION,
            'courseId' => (int) $course->getId(),
            'sessionId' => $sessionId,
            'generatedAt' => $this->now(),
            'analysis' => $analysis,
            'metadata' => $metadata,
        ];

        $this->extraFieldValuesRepository->updateItemData(
            $field,
            $user,
            $this->encodeStorage($contexts),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getStudentAnalysis(User $user, Course $course, ?Session $session): ?array
    {
        $stored = $this->readStorage(
            self::USER_ANALYSIS_VARIABLE,
            (int) $user->getId(),
            ExtraField::USER_FIELD_TYPE,
        );
        $contexts = $this->readContexts($stored);
        $sessionId = (int) ($session?->getId() ?? 0);
        $context = $contexts[$this->studentContextKey((int) $course->getId(), $sessionId)] ?? null;

        return \is_array($context) ? $context : null;
    }

    private function getOrCreateField(int $itemType, string $variable, string $label): ExtraField
    {
        $repository = $this->entityManager->getRepository(ExtraField::class);
        $field = $repository->findOneBy([
            'itemType' => $itemType,
            'variable' => $variable,
        ]);

        if ($field instanceof ExtraField) {
            return $field;
        }

        $field = (new ExtraField())
            ->setItemType($itemType)
            ->setValueType(ExtraField::FIELD_TYPE_TEXTAREA)
            ->setVariable($variable)
            ->setDescription('Internal structured storage for the Student Success AI Coach.')
            ->setDisplayText($label)
            ->setHelperText(null)
            ->setDefaultValue('')
            ->setFieldOrder(0)
            ->setVisibleToSelf(false)
            ->setVisibleToOthers(false)
            ->setChangeable(false)
            ->setFilter(false)
            ->setAutoRemove(false)
        ;

        $this->entityManager->persist($field);
        $this->entityManager->flush();

        return $field;
    }

    /**
     * @return array<string, mixed>
     */
    private function readStorage(string $variable, int $itemId, int $itemType): array
    {
        return $this->extraFieldValuesRepository->getJsonValueByVariableAndItem(
            $variable,
            $itemId,
            $itemType,
        ) ?? [];
    }

    /**
     * @param array<string, mixed> $storage
     *
     * @return array<string, mixed>
     */
    private function readContexts(array $storage): array
    {
        $contexts = $storage['contexts'] ?? [];

        return \is_array($contexts) ? $contexts : [];
    }

    /**
     * @param array<string, mixed> $contexts
     *
     * @throws JsonException
     */
    private function encodeStorage(array $contexts): string
    {
        return json_encode(
            [
                'version' => self::STORAGE_VERSION,
                'contexts' => $contexts,
            ],
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private function courseContextKey(int $sessionId): string
    {
        return 'session:'.$sessionId;
    }

    private function studentContextKey(int $courseId, int $sessionId): string
    {
        return 'course:'.$courseId.':session:'.$sessionId;
    }

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);
    }
}
