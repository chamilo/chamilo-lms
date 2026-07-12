<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final readonly class CourseProgressCsvManager
{
    use CourseProgressAccessHelperTrait;

    private const MAX_FILE_SIZE = 5_242_880;
    private const MAX_ROWS = 10_000;
    private const MAX_PLAN_ITEMS_PER_THEMATIC = 100;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CThematicRepository $thematicRepository,
        private ResourceLinkRepository $resourceLinkRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function export(Request $request): StreamedResponse
    {
        [$course, $session] = $this->resolveWritableContext($request);
        $rows = $this->buildExportRows($course, $session);
        $filename = 'course_progress_'.$course->getId().'.csv';

        $response = new StreamedResponse(static function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            if (false === $output) {
                throw new RuntimeException('The CSV output stream could not be opened.');
            }

            foreach ($rows as $row) {
                fputcsv($output, $row, ',', '"', '\\');
            }

            fclose($output);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    /**
     * @return array{
     *     importedThematics: int,
     *     importedPlans: int,
     *     importedAdvances: int,
     *     ignoredRows: int,
     *     replaced: bool,
     *     totalAverage: float
     * }
     */
    public function import(Request $request): array
    {
        [$course, $session] = $this->resolveWritableContext($request);
        $this->validateCsrfToken((string) $request->request->get('csrfToken', ''));

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('A valid CSV file is required.');
        }

        $size = $file->getSize();
        if (null !== $size && $size > self::MAX_FILE_SIZE) {
            throw new BadRequestHttpException('The CSV file is too large.');
        }

        if ('csv' !== strtolower($file->getClientOriginalExtension())) {
            throw new BadRequestHttpException('The uploaded file must use the CSV extension.');
        }

        $importData = $this->parseCsvFile($file);
        $replace = $request->request->getBoolean('replace');

        $this->entityManager->beginTransaction();

        try {
            if ($replace) {
                $this->removeCurrentContextThematics($course, $session);
            }

            foreach ($importData['thematics'] as $thematicData) {
                $thematic = (new CThematic())
                    ->setTitle($this->sanitizeTitle($thematicData['title']))
                    ->setContent($this->sanitizeContent($thematicData['content']))
                    ->setParent($course)
                    ->addCourseLink($course, $session)
                    ->setActive(true)
                ;

                $this->thematicRepository->create($thematic);

                foreach ($thematicData['plans'] as $planData) {
                    $plan = (new CThematicPlan())
                        ->setThematic($thematic)
                        ->setTitle(trim(strip_tags($planData['title'])))
                        ->setDescription($this->sanitizeContent($planData['description']))
                        ->setDescriptionType($planData['descriptionType'])
                    ;

                    $this->entityManager->persist($plan);
                }

                foreach ($thematicData['advances'] as $advanceData) {
                    $advance = (new CThematicAdvance())
                        ->setThematic($thematic)
                        ->setStartDate($advanceData['startDate'])
                        ->setDuration($advanceData['duration'])
                        ->setContent($this->sanitizeContent($advanceData['content']))
                        ->setDoneAdvance(false)
                        ->setAttendance(null)
                    ;

                    $this->entityManager->persist($advance);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }

        return [
            'importedThematics' => \count($importData['thematics']),
            'importedPlans' => $importData['planCount'],
            'importedAdvances' => $importData['advanceCount'],
            'ignoredRows' => $importData['ignoredRows'],
            'replaced' => $replace,
            'totalAverage' => $this->thematicRepository->calculateTotalAverageForCourse($course, $session),
        ];
    }

    /**
     * @return array{0: Course, 1: ?Session}
     */
    private function resolveWritableContext(Request $request): array
    {
        $course = $this->getCourseProgressCourse($request, $this->entityManager);
        $this->assertCourseProgressToolEnabled($this->entityManager, $course);
        $session = $this->getCourseProgressSession($request, $this->entityManager);
        $this->assertSessionBelongsToCourse($session, $course);

        if ($this->isCourseProgressStudentView($request, (int) $course->getId())
            || !$this->canManageCourseProgress(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
            )
        ) {
            throw new AccessDeniedHttpException('You are not allowed to transfer course progress in this context.');
        }

        return [$course, $session];
    }

    /**
     * @return list<list<string>>
     */
    private function buildExportRows(Course $course, ?Session $session): array
    {
        $rows = [['type', 'data1', 'data2', 'data3']];
        $timezone = $this->getUserTimezone();

        foreach ($this->thematicRepository->getThematicListForCourse($course, $session) as $thematic) {
            if (!$thematic instanceof CThematic) {
                continue;
            }

            $rows[] = [
                'title',
                $this->toPlainText($thematic->getTitle()),
                $this->toPlainText((string) $thematic->getContent()),
            ];

            $plans = $thematic->getPlans()->toArray();
            usort(
                $plans,
                static fn (CThematicPlan $first, CThematicPlan $second): int => $first->getDescriptionType() <=> $second->getDescriptionType(),
            );

            foreach ($plans as $plan) {
                if (!$plan instanceof CThematicPlan || '' === trim((string) $plan->getDescription())) {
                    continue;
                }

                $rows[] = [
                    'plan',
                    $this->toPlainText((string) $plan->getTitle()),
                    $this->toPlainText((string) $plan->getDescription()),
                ];
            }

            foreach ($thematic->getAdvances() as $advance) {
                if (!$advance instanceof CThematicAdvance) {
                    continue;
                }

                $localDate = DateTimeImmutable::createFromInterface($advance->getStartDate())
                    ->setTimezone($timezone)
                    ->format('Y-m-d H:i:s')
                ;

                $rows[] = [
                    'progress',
                    $localDate,
                    (string) $advance->getDuration(),
                    $this->toPlainText((string) $advance->getContent()),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array{
     *     thematics: list<array{
     *         title: string,
     *         content: string,
     *         plans: list<array{title: string, description: string, descriptionType: int}>,
     *         advances: list<array{startDate: DateTime, duration: int, content: string}>
     *     }>,
     *     planCount: int,
     *     advanceCount: int,
     *     ignoredRows: int
     * }
     */
    private function parseCsvFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (false === $path) {
            throw new BadRequestHttpException('The uploaded CSV file could not be read.');
        }

        $handle = fopen($path, 'rb');
        if (false === $handle) {
            throw new BadRequestHttpException('The uploaded CSV file could not be opened.');
        }

        $thematics = [];
        $currentIndex = null;
        $lineNumber = 0;
        $planCount = 0;
        $advanceCount = 0;
        $ignoredRows = 0;

        try {
            while (false !== ($row = fgetcsv($handle, 0, ',', '"', '\\'))) {
                ++$lineNumber;

                if ($lineNumber > self::MAX_ROWS) {
                    throw new BadRequestHttpException('The CSV file contains too many rows.');
                }

                $row = array_map(static fn (mixed $value): string => trim((string) $value), $row);
                if (1 === $lineNumber && isset($row[0])) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]) ?? $row[0];
                }

                if ($this->isEmptyCsvRow($row)) {
                    continue;
                }

                if (1 === $lineNumber) {
                    if ('type' !== strtolower($row[0] ?? '')) {
                        throw new BadRequestHttpException('The CSV header is invalid.');
                    }

                    continue;
                }

                if (\count($row) > 4) {
                    throw new BadRequestHttpException('The CSV row '.$lineNumber.' contains too many columns.');
                }

                $row = array_pad($row, 4, '');
                $type = strtolower($row[0]);

                switch ($type) {
                    case 'title':
                        if ('' === $row[1]) {
                            throw new BadRequestHttpException('The thematic title is required on CSV row '.$lineNumber.'.');
                        }

                        $thematics[] = [
                            'title' => $row[1],
                            'content' => $row[2],
                            'plans' => [],
                            'advances' => [],
                        ];
                        $currentIndex = array_key_last($thematics);

                        break;

                    case 'plan':
                        if (null === $currentIndex) {
                            throw new BadRequestHttpException('A thematic plan appears before a thematic title on CSV row '.$lineNumber.'.');
                        }

                        $descriptionType = \count($thematics[$currentIndex]['plans']) + 1;
                        if ($descriptionType > self::MAX_PLAN_ITEMS_PER_THEMATIC) {
                            throw new BadRequestHttpException('A thematic contains too many plan items.');
                        }

                        if (mb_strlen($row[1]) > 255) {
                            throw new BadRequestHttpException('A thematic plan title is too long on CSV row '.$lineNumber.'.');
                        }

                        $thematics[$currentIndex]['plans'][] = [
                            'title' => $row[1],
                            'description' => $row[2],
                            'descriptionType' => $descriptionType,
                        ];
                        ++$planCount;

                        break;

                    case 'progress':
                        if (null === $currentIndex) {
                            throw new BadRequestHttpException('A thematic advance appears before a thematic title on CSV row '.$lineNumber.'.');
                        }

                        $duration = (int) $row[2];
                        if ($duration < 1 || $duration > 100000) {
                            throw new BadRequestHttpException('The thematic advance duration is invalid on CSV row '.$lineNumber.'.');
                        }

                        $thematics[$currentIndex]['advances'][] = [
                            'startDate' => $this->parseImportedDate($row[1], $lineNumber),
                            'duration' => $duration,
                            'content' => $row[3],
                        ];
                        ++$advanceCount;

                        break;

                    default:
                        ++$ignoredRows;

                        break;
                }
            }
        } finally {
            fclose($handle);
        }

        if ([] === $thematics) {
            throw new BadRequestHttpException('The CSV file does not contain any thematic sections.');
        }

        return [
            'thematics' => $thematics,
            'planCount' => $planCount,
            'advanceCount' => $advanceCount,
            'ignoredRows' => $ignoredRows,
        ];
    }

    private function parseImportedDate(string $value, int $lineNumber): DateTime
    {
        if ('' === $value) {
            throw new BadRequestHttpException('The thematic advance date is required on CSV row '.$lineNumber.'.');
        }

        $formats = ['!Y-m-d H:i:s', '!Y-m-d H:i', DateTimeInterface::ATOM];
        $timezone = $this->getUserTimezone();
        $parsed = null;

        foreach ($formats as $format) {
            $candidate = DateTimeImmutable::createFromFormat($format, $value, $timezone);
            $errors = DateTimeImmutable::getLastErrors();

            if (false !== $candidate && (false === $errors || (0 === $errors['warning_count'] && 0 === $errors['error_count']))) {
                $parsed = $candidate;

                break;
            }
        }

        if (!$parsed instanceof DateTimeImmutable) {
            throw new BadRequestHttpException('The thematic advance date is invalid on CSV row '.$lineNumber.'.');
        }

        return DateTime::createFromImmutable($parsed->setTimezone(new DateTimeZone('UTC')));
    }

    private function removeCurrentContextThematics(Course $course, ?Session $session): void
    {
        foreach ($this->thematicRepository->getThematicListForCourse($course, $session) as $thematic) {
            if (!$thematic instanceof CThematic || !$this->thematicBelongsToExactContext($thematic, $course, $session)) {
                continue;
            }

            $this->resourceLinkRepository->removeByResourceInContext($thematic, $course, $session);
        }
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(
            new CsrfToken(CourseProgressThematicProvider::CSRF_TOKEN_ID, $token),
        )) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    private function sanitizeTitle(string $title): string
    {
        if ($this->isSettingEnabled('editor.save_titles_as_html')) {
            return $this->sanitizeContent($title);
        }

        return trim(strip_tags($title));
    }

    private function sanitizeContent(string $content): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function getUserTimezone(): DateTimeZone
    {
        $user = $this->security->getUser();
        $timezoneId = date_default_timezone_get();

        if ($user instanceof User && method_exists($user, 'getTimezone') && $user->getTimezone()) {
            $timezoneId = (string) $user->getTimezone();
        }

        try {
            return new DateTimeZone($timezoneId);
        } catch (Throwable) {
            return new DateTimeZone(date_default_timezone_get());
        }
    }

    /**
     * @param string[] $row
     */
    private function isEmptyCsvRow(array $row): bool
    {
        foreach ($row as $value) {
            if ('' !== trim($value)) {
                return false;
            }
        }

        return true;
    }

    private function toPlainText(string $value): string
    {
        return trim(strip_tags($value));
    }
}
