<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseProgress\CourseProgressList;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProviderInterface<CourseProgressList>
 */
final readonly class CourseProgressListProvider implements ProviderInterface
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseProgressList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourseProgressCourse($request, $this->entityManager);
        $this->assertCourseProgressToolEnabled($this->entityManager, $course);
        $session = $this->getCourseProgressSession($request, $this->entityManager);
        $this->assertSessionBelongsToCourse($session, $course);

        if (!$this->canReadCourseProgress($this->security, $this->settingsManager, $course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to view course progress in this context.');
        }

        $studentView = $this->isCourseProgressStudentView($request, (int) $course->getId());
        $canManage = !$studentView && $this->canManageCourseProgress(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
        );

        $list = new CourseProgressList();
        $list->courseId = (int) $course->getId();
        $list->sessionId = null !== $session ? (int) $session->getId() : null;
        $list->canManage = $canManage;
        $list->studentView = $studentView;
        $list->totalAverage = $this->thematicRepository->calculateTotalAverageForCourse($course, $session);
        $list->csrfToken = $canManage
            ? (string) $this->csrfTokenManager->getToken(CourseProgressThematicProvider::CSRF_TOKEN_ID)
            : '';
        $list->completionCsrfToken = $canManage
            ? (string) $this->csrfTokenManager->getToken(CourseProgressCompletionProcessor::CSRF_TOKEN_ID)
            : '';

        $dateFormatter = $this->createDateFormatter($request);
        $thematics = $this->thematicRepository->getThematicListForCourse($course, $session);

        $thematicCount = \count($thematics);

        foreach ($thematics as $position => $thematic) {
            if (!$thematic instanceof CThematic || null === $thematic->getIid()) {
                continue;
            }

            $normalizedThematic = $this->normalizeThematic(
                $thematic,
                $course,
                $session,
                $dateFormatter,
                $canManage,
                $position,
                $thematicCount,
            );

            foreach ($normalizedThematic['advances'] as $advance) {
                if (true === ($advance['doneAdvance'] ?? false)) {
                    $list->lastDoneAdvanceId = (int) $advance['iid'];
                }
            }

            $list->items[] = $normalizedThematic;
        }

        $list->totalItems = \count($list->items);

        return $list;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeThematic(
        CThematic $thematic,
        Course $course,
        ?Session $session,
        IntlDateFormatter $dateFormatter,
        bool $canManage,
        int $position,
        int $thematicCount,
    ): array {
        $resourceNode = $thematic->getResourceNode();
        $contextLink = $thematic->getFirstResourceLinkFromCourseSession($course, $session);

        if (!$contextLink instanceof ResourceLink && null !== $session) {
            $contextLink = $thematic->getFirstResourceLinkFromCourseSession($course);
        }

        $sourceSession = $contextLink?->getSession();
        $belongsToExactContext = $this->thematicBelongsToExactContext($thematic, $course, $session);
        $canEdit = $canManage
            && $belongsToExactContext
            && null !== $resourceNode
            && $this->security->isGranted('EDIT', $resourceNode);
        $canDelete = $canManage
            && $belongsToExactContext
            && null !== $resourceNode
            && $this->security->isGranted('DELETE', $resourceNode);
        $plans = [];
        $advances = [];
        $doneAdvances = 0;

        foreach ($thematic->getPlans() as $plan) {
            if (!$plan instanceof CThematicPlan || null === $plan->getIid()) {
                continue;
            }

            $plans[] = [
                'iid' => (int) $plan->getIid(),
                'title' => $this->sanitizeHtml((string) $plan->getTitle()),
                'description' => $this->sanitizeHtml((string) $plan->getDescription()),
                'descriptionType' => $plan->getDescriptionType(),
            ];
        }

        foreach ($thematic->getAdvances() as $advance) {
            if (!$advance instanceof CThematicAdvance || null === $advance->getIid()) {
                continue;
            }

            $isDone = true === $advance->getDoneAdvance();
            if ($isDone) {
                ++$doneAdvances;
            }

            $startDate = $advance->getStartDate();
            $advances[] = [
                'iid' => (int) $advance->getIid(),
                'content' => $this->sanitizeHtml((string) $advance->getContent()),
                'startDate' => $startDate instanceof DateTimeInterface
                    ? $startDate->format(DateTimeInterface::ATOM)
                    : null,
                'formattedStartDate' => $startDate instanceof DateTimeInterface
                    ? $this->formatDate($startDate, $dateFormatter)
                    : '',
                'duration' => (int) $advance->getDuration(),
                'doneAdvance' => $isDone,
            ];
        }

        $totalAdvances = \count($advances);
        $average = $totalAdvances > 0 ? round(($doneAdvances * 100) / $totalAdvances) : 0.0;

        return [
            'iid' => (int) $thematic->getIid(),
            'title' => $this->sanitizeHtml($thematic->getTitle()),
            'content' => $this->sanitizeHtml((string) $thematic->getContent()),
            'resourceNodeId' => null !== $resourceNode?->getId() ? (int) $resourceNode->getId() : null,
            'sessionId' => null !== $sourceSession?->getId() ? (int) $sourceSession->getId() : null,
            'isInheritedFromCourse' => null !== $session && null === $sourceSession,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
            'canCopy' => $canEdit,
            'canMove' => $canEdit && null === $session,
            'canMoveUp' => $canEdit && null === $session && $position > 0,
            'canMoveDown' => $canEdit && null === $session && $position < $thematicCount - 1,
            'average' => $average,
            'plans' => $plans,
            'advances' => $advances,
        ];
    }

    private function createDateFormatter(Request $request): IntlDateFormatter
    {
        $timezoneId = date_default_timezone_get();
        $user = $this->security->getUser();

        if ($user instanceof User && method_exists($user, 'getTimezone') && $user->getTimezone()) {
            $timezoneId = (string) $user->getTimezone();
        }

        return new IntlDateFormatter(
            $request->getLocale(),
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            $timezoneId,
        );
    }

    private function formatDate(DateTimeInterface $date, IntlDateFormatter $dateFormatter): string
    {
        $formattedDate = $dateFormatter->format($date);

        return false === $formattedDate ? $date->format('Y-m-d H:i') : $formattedDate;
    }

    private function sanitizeHtml(string $content): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
