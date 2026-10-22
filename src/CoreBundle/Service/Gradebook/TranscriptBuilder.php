<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
use Chamilo\CoreBundle\Settings\CertificateSettingsSchema;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Tracking;

/**
 * Builds the academic transcript of a user from their gradebook certificates: one row per course
 * (or course of a session) they got a certificate for, with the letter and grade points of the
 * grading scale, the final grade and the GPA. The same data feeds the unofficial transcript and
 * the signed snapshot of an official one.
 */
final readonly class TranscriptBuilder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
        private TrackEDefaultRepository $trackEDefaultRepository,
    ) {}

    /**
     * @return array{
     *     institution: array{name: string, address: string, url: string},
     *     programTitle: string,
     *     student: array{id: int, fullName: string, studentNumber: string, address: list<string>},
     *     courses: list<array<string, mixed>>,
     *     finalGrade: int|null,
     *     gpa: float|null,
     *     gradingScale: list<array{min: float, letter: string, points: float}>,
     *     gpaNote: string,
     *     signatory: array{name: string, title: string}
     * }
     */
    public function build(User $user, AccessUrl $url): array
    {
        $gradingScale = $this->getGradingScale();
        $courses = [];
        foreach ($this->getCertificates($user, $url) as $certificate) {
            $courses[] = $this->buildCourseRow($certificate, $user, $gradingScale);
        }
        usort($courses, static fn (array $a, array $b): int => [$a['completedAt'], $a['title']] <=> [$b['completedAt'], $b['title']]);

        $finalGrade = null;
        $gpa = null;
        if ([] !== $courses) {
            $finalGrade = (int) round(array_sum(array_column($courses, 'score')) / \count($courses));
            $gpa = round(array_sum(array_column($courses, 'gradePoints')) / \count($courses), 2);
        }

        return [
            'institution' => [
                'name' => $this->getSetting('platform.institution'),
                'address' => $this->getSetting('platform.institution_address'),
                'url' => $this->getSetting('platform.institution_url'),
            ],
            'programTitle' => $this->getSetting('certificate.transcript_program_title'),
            'student' => [
                'id' => (int) $user->getId(),
                'fullName' => $user->getFullName(),
                'studentNumber' => $this->getUserExtraFieldValue($user, $this->getSetting('certificate.transcript_student_number_extra_field')),
                'address' => $this->getAddress($user),
            ],
            'courses' => $courses,
            'finalGrade' => $finalGrade,
            'gpa' => $gpa,
            'gradingScale' => $gradingScale,
            'gpaNote' => $this->getSetting('certificate.transcript_gpa_note'),
            'signatory' => [
                'name' => $this->getSetting('certificate.transcript_signatory_name'),
                'title' => $this->getSetting('certificate.transcript_signatory_title'),
            ],
        ];
    }

    /**
     * The grading scale of the settings, from the highest minimum score down. An invalid setting
     * falls back to the default scale rather than printing transcripts without grades.
     *
     * @return list<array{min: float, letter: string, points: float}>
     */
    public function getGradingScale(): array
    {
        $scale = $this->parseGradingScale($this->getSetting('certificate.transcript_grading_scale'));

        return $scale ?? $this->parseGradingScale(CertificateSettingsSchema::DEFAULT_TRANSCRIPT_GRADING_SCALE) ?? [];
    }

    /**
     * @return list<array{min: float, letter: string, points: float}>|null
     */
    private function parseGradingScale(string $json): ?array
    {
        $items = json_decode($json, true);
        if (!\is_array($items) || [] === $items) {
            return null;
        }

        $scale = [];
        foreach ($items as $item) {
            if (!\is_array($item) || !is_numeric($item['min'] ?? null) || !is_numeric($item['points'] ?? null)
                || !\is_string($item['letter'] ?? null) || '' === trim($item['letter'])
            ) {
                return null;
            }
            $scale[] = ['min' => (float) $item['min'], 'letter' => trim($item['letter']), 'points' => (float) $item['points']];
        }
        usort($scale, static fn (array $a, array $b): int => $b['min'] <=> $a['min']);

        return $scale;
    }

    /**
     * The certificates "My certificates" lists: those of the root gradebook of a course of this
     * access URL that the user is still subscribed to.
     *
     * @return list<GradebookCertificate>
     */
    private function getCertificates(User $user, AccessUrl $url): array
    {
        $certificates = [];
        foreach ($this->entityManager->getRepository(GradebookCertificate::class)->findBy(['user' => $user]) as $certificate) {
            $category = $certificate->getCategory();
            if (!$category instanceof GradebookCategory
                || null !== $category->getParent()
                || !$category->getGenerateCertificates()
                || !$this->courseBelongsToUrl($category->getCourse(), $url)
                || !$this->isSubscribed($user, $category->getCourse(), $category->getSession())
            ) {
                continue;
            }
            $certificates[] = $certificate;
        }

        return $certificates;
    }

    /**
     * @param list<array{min: float, letter: string, points: float}> $gradingScale
     *
     * @return array<string, mixed>
     */
    private function buildCourseRow(GradebookCertificate $certificate, User $user, array $gradingScale): array
    {
        $category = $certificate->getCategory();
        $course = $category->getCourse();
        $session = $category->getSession();
        $score = round((float) $certificate->getScoreCertificate(), 2);
        $grade = $this->getGrade($score, $gradingScale);
        $hours = $this->getCourseHours($course);
        $completedHours = 'true' === $this->getSetting('certificate.transcript_completed_hours_are_required_hours')
            ? $hours
            : $this->getTimeSpentHours($user, $course, $session);

        return [
            'certificateId' => (int) $certificate->getId(),
            'courseId' => (int) $course->getId(),
            'code' => 'department_name' === $this->getSetting('certificate.transcript_course_code_source')
                ? (string) $course->getDepartmentName()
                : (string) $course->getVisualCode(),
            'title' => (string) $course->getTitle(),
            'sessionId' => $session?->getId(),
            'sessionTitle' => $session?->getTitle(),
            'enrolledAt' => $this->getEnrollmentDate($user, $course, $session)?->format('Y-m-d'),
            'requiredHours' => $hours,
            'completedHours' => $completedHours,
            'completedAt' => $certificate->getCreatedAt()->format('Y-m-d'),
            'score' => $score,
            'letter' => $grade['letter'],
            'gradePoints' => $grade['points'],
        ];
    }

    /**
     * @param list<array{min: float, letter: string, points: float}> $gradingScale
     *
     * @return array{letter: string, points: float}
     */
    private function getGrade(float $score, array $gradingScale): array
    {
        $roundedScore = round($score);
        foreach ($gradingScale as $grade) {
            if ($roundedScore >= $grade['min']) {
                return ['letter' => $grade['letter'], 'points' => $grade['points']];
            }
        }

        return ['letter' => '', 'points' => 0.0];
    }

    private function getCourseHours(Course $course): ?float
    {
        $variable = $this->getSetting('certificate.transcript_hours_extra_field');
        if ('' !== $variable) {
            $value = $this->extraFieldValuesRepository
                ->getValueByVariableAndItem($variable, (int) $course->getId(), ExtraField::COURSE_FIELD_TYPE)
                ?->getFieldValue()
            ;

            return is_numeric($value) ? (float) $value : null;
        }

        // The course duration is stored in seconds.
        $duration = $course->getDuration();

        return null !== $duration && $duration > 0 ? round($duration / 3600, 2) : null;
    }

    private function getTimeSpentHours(User $user, Course $course, ?Session $session): float
    {
        $seconds = (int) Tracking::get_time_spent_on_the_course((int) $user->getId(), (int) $course->getId(), (int) $session?->getId());

        return round(max(0, $seconds) / 3600, 2);
    }

    private function getEnrollmentDate(User $user, Course $course, ?Session $session): ?DateTimeInterface
    {
        if (null === $session) {
            return $this->trackEDefaultRepository->getUserCourseRegistrationAt((int) $course->getId(), (int) $user->getId());
        }

        $subscription = $this->entityManager->getRepository(SessionRelUser::class)->findOneBy([
            'session' => $session,
            'user' => $user,
            'relationType' => Session::STUDENT,
        ]);

        return $subscription?->getRegisteredAt();
    }

    /**
     * @return list<string>
     */
    private function getAddress(User $user): array
    {
        $lines = [trim((string) $user->getAddress())];
        foreach (explode(',', $this->getSetting('certificate.transcript_address_extra_fields')) as $variable) {
            $lines[] = $this->getUserExtraFieldValue($user, trim($variable));
        }

        return array_values(array_filter($lines, static fn (string $line): bool => '' !== $line));
    }

    private function getUserExtraFieldValue(User $user, string $variable): string
    {
        if ('' === $variable) {
            return '';
        }

        return trim((string) $this->extraFieldValuesRepository
            ->getValueByVariableAndItem($variable, (int) $user->getId(), ExtraField::USER_FIELD_TYPE)
            ?->getFieldValue());
    }

    private function isSubscribed(User $user, Course $course, ?Session $session): bool
    {
        if (null === $session) {
            return null !== $this->entityManager->getRepository(CourseRelUser::class)->findOneBy([
                'course' => $course,
                'user' => $user,
            ]);
        }

        return null !== $this->entityManager->getRepository(SessionRelCourseRelUser::class)->findOneBy([
            'course' => $course,
            'session' => $session,
            'user' => $user,
        ]);
    }

    private function courseBelongsToUrl(Course $course, AccessUrl $url): bool
    {
        foreach ($course->getUrls() as $relation) {
            if ($relation->getUrl()->getId() === $url->getId()) {
                return true;
            }
        }

        return false;
    }

    private function getSetting(string $name): string
    {
        return trim((string) $this->settingsManager->getSetting($name, true));
    }
}
