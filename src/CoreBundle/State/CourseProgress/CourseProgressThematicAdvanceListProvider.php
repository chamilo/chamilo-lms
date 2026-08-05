<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseProgress\CourseProgressThematicAdvanceList;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use IntlDateFormatter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProviderInterface<CourseProgressThematicAdvanceList>
 */
final readonly class CourseProgressThematicAdvanceListProvider implements ProviderInterface
{
    use CourseProgressAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CThematicRepository $thematicRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseProgressThematicAdvanceList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourseProgressCourse($request, $this->entityManager);
        $this->assertCourseProgressToolEnabled($this->entityManager, $course);
        $session = $this->getCourseProgressSession($request, $this->entityManager);
        $this->assertSessionBelongsToCourse($session, $course);
        $this->assertCanManage($request, $course, $session);

        $thematicId = isset($uriVariables['thematicId']) ? (int) $uriVariables['thematicId'] : 0;
        $thematic = $this->getEditableThematic($thematicId, $course, $session);
        $dateFormatter = $this->createDateFormatter($request);
        $timezone = $this->getUserTimezone();

        $result = new CourseProgressThematicAdvanceList();
        $result->thematicId = (int) $thematic->getIid();
        $result->thematicTitle = $this->sanitizeHtml($thematic->getTitle());
        $result->thematicContent = $this->sanitizeHtml((string) $thematic->getContent());
        $result->csrfToken = (string) $this->csrfTokenManager->getToken(
            CourseProgressThematicAdvanceProvider::CSRF_TOKEN_ID,
        );
        $result->canEdit = true;

        foreach ($thematic->getAdvances() as $advance) {
            if (!$advance instanceof CThematicAdvance || null === $advance->getIid()) {
                continue;
            }

            $startDate = $advance->getStartDate();
            $attendance = $advance->getAttendance();
            $result->items[] = [
                'iid' => (int) $advance->getIid(),
                'startDate' => $startDate instanceof DateTimeInterface
                    ? $this->formatIsoDate($startDate, $timezone)
                    : null,
                'formattedStartDate' => $startDate instanceof DateTimeInterface
                    ? $this->formatDate($startDate, $dateFormatter)
                    : '',
                'duration' => (int) $advance->getDuration(),
                'content' => $this->sanitizeHtml((string) $advance->getContent()),
                'doneAdvance' => true === $advance->getDoneAdvance(),
                'attendanceId' => null !== $attendance?->getIid() ? (int) $attendance->getIid() : null,
                'attendanceTitle' => null !== $attendance ? trim(strip_tags($attendance->getTitle())) : '',
            ];
        }

        $result->totalItems = \count($result->items);

        return $result;
    }

    private function assertCanManage(Request $request, Course $course, ?Session $session): void
    {
        if (!$this->isCourseProgressStudentView($request, (int) $course->getId())
            && $this->canManageCourseProgress(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
            )
        ) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage thematic advances in this context.');
    }

    private function getEditableThematic(int $thematicId, Course $course, ?Session $session): CThematic
    {
        if ($thematicId <= 0) {
            throw new BadRequestHttpException('A valid thematic id is required.');
        }

        $thematic = $this->thematicRepository->find($thematicId);
        if (!$thematic instanceof CThematic) {
            throw new NotFoundHttpException('The requested thematic was not found.');
        }

        if (!$this->thematicBelongsToExactContext($thematic, $course, $session)) {
            throw new AccessDeniedHttpException('The requested thematic does not belong to the current course context.');
        }

        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit thematic advances.');
        }

        return $thematic;
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

    private function getUserTimezone(): DateTimeZone
    {
        $timezoneId = date_default_timezone_get();
        $user = $this->security->getUser();

        if ($user instanceof User && method_exists($user, 'getTimezone') && $user->getTimezone()) {
            $timezoneId = (string) $user->getTimezone();
        }

        try {
            return new DateTimeZone($timezoneId);
        } catch (Exception) {
            return new DateTimeZone(date_default_timezone_get());
        }
    }

    private function formatDate(DateTimeInterface $date, IntlDateFormatter $dateFormatter): string
    {
        $formattedDate = $dateFormatter->format($date);

        return false === $formattedDate ? $date->format('Y-m-d H:i') : $formattedDate;
    }

    private function formatIsoDate(DateTimeInterface $date, DateTimeZone $timezone): string
    {
        return DateTimeImmutable::createFromInterface($date)
            ->setTimezone($timezone)
            ->format(DateTimeInterface::ATOM)
        ;
    }

    private function sanitizeHtml(string $content): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
