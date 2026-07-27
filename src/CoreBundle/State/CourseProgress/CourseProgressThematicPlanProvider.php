<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseProgress\CourseProgressThematicPlan;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Doctrine\ORM\EntityManagerInterface;
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
 * @implements ProviderInterface<CourseProgressThematicPlan>
 */
final readonly class CourseProgressThematicPlanProvider implements ProviderInterface
{
    use CourseProgressAccessHelperTrait;

    public const string CSRF_TOKEN_ID = 'course_progress_thematic_plan';

    /**
     * @var array<int, string>
     */
    private const array DEFAULT_TITLES = [
        1 => 'Objectives',
        2 => 'Skills to acquire',
        3 => 'Methodology',
        4 => 'Infrastructure',
        5 => 'Assessment',
        6 => 'Others',
    ];

    /**
     * @var array<int, string>
     */
    private const array DEFAULT_HELP = [
        1 => 'What should the end results be when the learner has completed the course? What are the activities performed during the course?',
        2 => 'What skills are to be acquired bu the end of this thematic section?',
        3 => 'What methods and activities help achieve the objectives of the course?  What would the schedule be?',
        4 => 'What infrastructure is necessary to achieve the goals of this topic normally?',
        5 => 'How will learners be assessed? Are there strategies to develop in order to master the topic?',
    ];

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseProgressThematicPlan
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

        return $this->buildResponse($thematic);
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

        throw new AccessDeniedHttpException('You are not allowed to manage thematic plans in this context.');
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
            throw new AccessDeniedHttpException('You are not allowed to edit this thematic plan.');
        }

        return $thematic;
    }

    private function buildResponse(CThematic $thematic): CourseProgressThematicPlan
    {
        $result = new CourseProgressThematicPlan();
        $result->thematicId = (int) $thematic->getIid();
        $result->thematicTitle = $this->sanitizeHtml($thematic->getTitle());
        $result->thematicContent = $this->sanitizeHtml((string) $thematic->getContent());
        $result->items = $this->buildItems($thematic);
        $result->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $result->canEdit = true;

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(CThematic $thematic): array
    {
        $existingPlans = [];

        foreach ($thematic->getPlans() as $plan) {
            if (!$plan instanceof CThematicPlan || null === $plan->getIid()) {
                continue;
            }

            $existingPlans[$plan->getDescriptionType()] = $plan;
        }

        ksort($existingPlans);
        $items = [];

        foreach (self::DEFAULT_TITLES as $descriptionType => $defaultTitle) {
            $plan = $existingPlans[$descriptionType] ?? null;
            $items[] = $this->normalizeItem($descriptionType, $plan, $defaultTitle, false);
            unset($existingPlans[$descriptionType]);
        }

        foreach ($existingPlans as $descriptionType => $plan) {
            if ($descriptionType < 7) {
                continue;
            }

            $items[] = $this->normalizeItem($descriptionType, $plan, '', true);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeItem(
        int $descriptionType,
        ?CThematicPlan $plan,
        string $defaultTitle,
        bool $isCustom,
    ): array {
        return [
            'iid' => $plan?->getIid(),
            'descriptionType' => $descriptionType,
            'title' => $plan instanceof CThematicPlan ? trim(strip_tags($plan->getTitle())) : $defaultTitle,
            'description' => $plan instanceof CThematicPlan
                ? $this->sanitizeHtml((string) $plan->getDescription())
                : '',
            'defaultTitle' => $defaultTitle,
            'help' => self::DEFAULT_HELP[$descriptionType] ?? '',
            'isCustom' => $isCustom,
            'usesDefaultTitle' => !$plan instanceof CThematicPlan && '' !== $defaultTitle,
        ];
    }

    private function sanitizeHtml(string $content): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
