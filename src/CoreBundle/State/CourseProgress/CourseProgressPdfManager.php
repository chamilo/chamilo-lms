<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use Chamilo\CoreBundle\Component\Mpdf\SafeMpdfHttpClient;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use const COURSEMANAGERLOWSECURITY;
use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PHP_SESSION_ACTIVE;

final readonly class CourseProgressPdfManager
{
    use CourseProgressAccessHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CThematicRepository $thematicRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private TranslatorInterface $translator,
    ) {}

    public function export(Request $request, ?int $thematicId = null): Response
    {
        [$course, $session] = $this->resolveWritableContext($request);
        $thematics = $this->resolveThematics($course, $session, $thematicId);
        $dateFormatter = $this->createDateFormatter($request);
        $data = [];

        foreach ($thematics as $thematic) {
            $data[] = $this->normalizeThematic($thematic, $dateFormatter);
        }

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $orientation = $this->resolveOrientation();
        $title = $this->translator->trans('Thematic');
        $filename = $this->buildFilename($title, $thematicId, $thematics);
        $html = $this->buildHtml($data);

        try {
            $mpdf = new Mpdf([
                'format' => 'A4',
                'orientation' => $orientation,
                'tempDir' => api_get_path(SYS_ARCHIVE_PATH).'mpdf/',
            ], SafeMpdfHttpClient::container());
            $mpdf->SetTitle($title);
            $mpdf->WriteHTML($html);

            return new Response(
                $mpdf->Output('', Destination::INLINE),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                    'Cache-Control' => 'no-store, private',
                ],
            );
        } catch (MpdfException $exception) {
            throw new RuntimeException('Failed to generate course progress PDF: '.$exception->getMessage(), 500, $exception);
        }
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
            throw new AccessDeniedHttpException('You are not allowed to export course progress in this context.');
        }

        return [$course, $session];
    }

    /**
     * @return list<CThematic>
     */
    private function resolveThematics(Course $course, ?Session $session, ?int $thematicId): array
    {
        $thematics = array_values(array_filter(
            $this->thematicRepository->getThematicListForCourse($course, $session),
            static fn (mixed $thematic): bool => $thematic instanceof CThematic,
        ));

        if (null === $thematicId) {
            return $thematics;
        }

        foreach ($thematics as $thematic) {
            if ($thematicId === $thematic->getIid()) {
                return [$thematic];
            }
        }

        throw new NotFoundHttpException('The requested thematic was not found in the current course context.');
    }

    /**
     * @return array{
     *     title: string,
     *     content: string,
     *     plans: list<array{title: string, description: string}>,
     *     advances: list<array{duration: int, startDate: string, content: string}>
     * }
     */
    private function normalizeThematic(CThematic $thematic, IntlDateFormatter $dateFormatter): array
    {
        $plans = array_values(array_filter(
            $thematic->getPlans()->toArray(),
            static fn (mixed $plan): bool => $plan instanceof CThematicPlan,
        ));
        usort(
            $plans,
            static function (CThematicPlan $first, CThematicPlan $second): int {
                $typeComparison = $first->getDescriptionType() <=> $second->getDescriptionType();

                if (0 !== $typeComparison) {
                    return $typeComparison;
                }

                return (int) $first->getIid() <=> (int) $second->getIid();
            },
        );

        $normalizedPlans = [];
        foreach ($plans as $plan) {
            $normalizedPlans[] = [
                'title' => $this->sanitizeHtml((string) $plan->getTitle()),
                'description' => $this->sanitizeHtml((string) $plan->getDescription()),
            ];
        }

        $advances = array_values(array_filter(
            $thematic->getAdvances()->toArray(),
            static fn (mixed $advance): bool => $advance instanceof CThematicAdvance,
        ));
        usort(
            $advances,
            static function (CThematicAdvance $first, CThematicAdvance $second): int {
                $dateComparison = $first->getStartDate() <=> $second->getStartDate();

                if (0 !== $dateComparison) {
                    return $dateComparison;
                }

                return (int) $first->getIid() <=> (int) $second->getIid();
            },
        );

        $normalizedAdvances = [];
        foreach ($advances as $advance) {
            $normalizedAdvances[] = [
                'duration' => (int) $advance->getDuration(),
                'startDate' => $this->formatDate($advance->getStartDate(), $dateFormatter),
                'content' => $this->sanitizeHtml((string) $advance->getContent()),
            ];
        }

        return [
            'title' => $this->sanitizeHtml($thematic->getTitle()),
            'content' => $this->sanitizeHtml((string) $thematic->getContent()),
            'plans' => $normalizedPlans,
            'advances' => $normalizedAdvances,
        ];
    }

    /**
     * @param list<array{
     *     title: string,
     *     content: string,
     *     plans: list<array{title: string, description: string}>,
     *     advances: list<array{duration: int, startDate: string, content: string}>
     * }> $thematics
     */
    private function buildHtml(array $thematics): string
    {
        $thematicLabel = $this->escapeText($this->translator->trans('Thematic'));
        $planLabel = $this->escapeText($this->translator->trans('Thematic plan'));
        $advanceLabel = $this->escapeText($this->translator->trans('Thematic advance'));
        $hoursLabel = $this->escapeText($this->translator->trans('hours'));
        $drhLabel = $this->escapeText($this->translator->trans('Drh'));
        $teacherLabel = $this->escapeText($this->translator->trans('Teacher'));
        $dateLabel = $this->escapeText($this->translator->trans('Date'));

        $html = '<style>
            body { font-family: sans-serif; font-size: 10pt; color: #222; }
            table.course-progress { border-collapse: collapse; table-layout: fixed; width: 100%; }
            .course-progress th, .course-progress td { border: 1px solid #888; padding: 8px; vertical-align: top; }
            .course-progress th { background: #e5e5e5; text-align: center; }
            .course-progress h4 { margin: 0 0 8px 0; font-size: 11pt; }
            .course-progress .section { margin-bottom: 12px; }
            table.signatures { border-collapse: separate; border-spacing: 18px 0; margin-top: 42px; width: 100%; }
            .signatures td { border-top: 1px solid #444; padding-top: 6px; text-align: center; width: 33.33%; }
        </style>';
        $html .= '<table class="course-progress">';
        $html .= '<thead><tr>';
        $html .= '<th style="width:30%">'.$thematicLabel.'</th>';
        $html .= '<th style="width:50%">'.$planLabel.'</th>';
        $html .= '<th style="width:20%">'.$advanceLabel.'</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($thematics as $thematic) {
            $html .= '<tr>';
            $html .= '<td><h4>'.$thematic['title'].'</h4>'.$thematic['content'].'</td>';
            $html .= '<td>';
            foreach ($thematic['plans'] as $plan) {
                $html .= '<div class="section"><h4>'.$plan['title'].'</h4>'.$plan['description'].'</div>';
            }
            $html .= '</td>';
            $html .= '<td>';
            foreach ($thematic['advances'] as $advance) {
                $html .= '<div class="section"><h4>'.$advance['duration'].' '.$hoursLabel.'</h4>';
                $html .= '<div>'.$this->escapeText($advance['startDate']).'</div>';
                $html .= $advance['content'].'</div>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<table class="signatures"><tr>';
        $html .= '<td>'.$drhLabel.'</td>';
        $html .= '<td>'.$teacherLabel.'</td>';
        $html .= '<td>'.$dateLabel.'</td>';
        $html .= '</tr></table>';

        return $html;
    }

    private function resolveOrientation(): string
    {
        $setting = strtolower(trim((string) $this->settingsManager->getSetting(
            'document.thematic_pdf_orientation',
            true,
        )));

        return 'portrait' === $setting ? 'P' : 'L';
    }

    /**
     * @param list<CThematic> $thematics
     */
    private function buildFilename(string $title, ?int $thematicId, array $thematics): string
    {
        $parts = [$title];

        if (null !== $thematicId && isset($thematics[0])) {
            $parts[] = $this->toPlainText($thematics[0]->getTitle());
        }

        $parts[] = (new DateTimeImmutable('now', $this->getUserTimezone()))->format('Y-m-d_H-i-s');
        $filename = implode('-', array_filter($parts));
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?? 'thematic';
        $filename = trim($filename, '-._');

        return ('' === $filename ? 'thematic' : $filename).'.pdf';
    }

    private function createDateFormatter(Request $request): IntlDateFormatter
    {
        return new IntlDateFormatter(
            $request->getLocale(),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            $this->getUserTimezone()->getName(),
        );
    }

    private function formatDate(DateTimeInterface $date, IntlDateFormatter $dateFormatter): string
    {
        $localDate = DateTimeImmutable::createFromInterface($date)->setTimezone($this->getUserTimezone());
        $formatted = $dateFormatter->format($localDate);

        return false === $formatted ? $localDate->format('Y-m-d H:i') : $formatted;
    }

    private function getUserTimezone(): DateTimeZone
    {
        $timezone = date_default_timezone_get();
        $user = $this->security->getUser();

        if ($user instanceof User && method_exists($user, 'getTimezone') && $user->getTimezone()) {
            $timezone = (string) $user->getTimezone();
        }

        return new DateTimeZone($timezone);
    }

    private function sanitizeHtml(string $content): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function toPlainText(string $content): string
    {
        return trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function escapeText(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
