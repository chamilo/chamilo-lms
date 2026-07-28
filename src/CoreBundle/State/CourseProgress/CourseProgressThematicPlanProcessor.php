<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
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
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProcessorInterface<CourseProgressThematicPlan, CourseProgressThematicPlan>
 */
final readonly class CourseProgressThematicPlanProcessor implements ProcessorInterface
{
    use CourseProgressAccessHelperTrait;

    private const int MAX_ITEMS = 100;

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
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CourseProgressThematicPlan {
        if (!$data instanceof CourseProgressThematicPlan) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourseProgressCourse($request, $this->entityManager);
        $this->assertCourseProgressToolEnabled($this->entityManager, $course);
        $session = $this->getCourseProgressSession($request, $this->entityManager);
        $this->assertSessionBelongsToCourse($session, $course);
        $this->assertCanManage($request, $course, $session);
        $this->validateCsrfToken($data->csrfToken);

        $thematicId = isset($uriVariables['thematicId']) ? (int) $uriVariables['thematicId'] : 0;
        $thematic = $this->getEditableThematic($thematicId, $course, $session);
        $submittedItems = $this->normalizeSubmittedItems($data->items);

        if ($data->addNewItem && \count($submittedItems) >= self::MAX_ITEMS) {
            throw new BadRequestHttpException('The thematic plan item limit has been reached.');
        }

        foreach (self::DEFAULT_TITLES as $descriptionType => $defaultTitle) {
            if (isset($submittedItems[$descriptionType])) {
                continue;
            }

            $submittedItems[$descriptionType] = [
                'descriptionType' => $descriptionType,
                'title' => $defaultTitle,
                'description' => '',
            ];
        }

        ksort($submittedItems);
        $this->saveItems($thematic, $submittedItems, $data->addNewItem);

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

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(
            new CsrfToken(CourseProgressThematicPlanProvider::CSRF_TOKEN_ID, $token),
        )) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, array{descriptionType: int, title: string, description: string}>
     */
    private function normalizeSubmittedItems(array $items): array
    {
        if (\count($items) > self::MAX_ITEMS) {
            throw new BadRequestHttpException('Too many thematic plan items were submitted.');
        }

        $normalized = [];

        foreach ($items as $item) {
            if (!\is_array($item)) {
                throw new BadRequestHttpException('A thematic plan item is invalid.');
            }

            $descriptionType = (int) ($item['descriptionType'] ?? 0);
            if ($descriptionType <= 0 || $descriptionType > 10000) {
                throw new BadRequestHttpException('A thematic plan description type is invalid.');
            }

            if (isset($normalized[$descriptionType])) {
                throw new BadRequestHttpException('A thematic plan description type was submitted more than once.');
            }

            $titleValue = $item['title'] ?? '';
            $descriptionValue = $item['description'] ?? '';

            if (!\is_scalar($titleValue) || !\is_scalar($descriptionValue)) {
                throw new BadRequestHttpException('A thematic plan value is invalid.');
            }

            $title = trim(strip_tags((string) $titleValue));
            if (mb_strlen($title) > 255) {
                throw new BadRequestHttpException('A thematic plan title is too long.');
            }

            $normalized[$descriptionType] = [
                'descriptionType' => $descriptionType,
                'title' => $title,
                'description' => $this->sanitizeContent(trim((string) $descriptionValue)),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int, array{descriptionType: int, title: string, description: string}> $submittedItems
     */
    private function saveItems(CThematic $thematic, array $submittedItems, bool $addNewItem): void
    {
        $existingPlans = [];

        foreach ($thematic->getPlans() as $plan) {
            if (!$plan instanceof CThematicPlan) {
                continue;
            }

            $existingPlans[$plan->getDescriptionType()] = $plan;
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            foreach ($submittedItems as $descriptionType => $submittedItem) {
                $plan = $existingPlans[$descriptionType] ?? null;

                if (!$plan instanceof CThematicPlan) {
                    $plan = new CThematicPlan();
                    $plan
                        ->setThematic($thematic)
                        ->setDescriptionType($descriptionType)
                    ;
                    $thematic->getPlans()->add($plan);
                    $this->entityManager->persist($plan);
                }

                $plan
                    ->setTitle($submittedItem['title'])
                    ->setDescription($submittedItem['description'])
                ;
            }

            foreach ($existingPlans as $descriptionType => $plan) {
                if ($descriptionType < 7 || isset($submittedItems[$descriptionType])) {
                    continue;
                }

                $thematic->getPlans()->removeElement($plan);
                $this->entityManager->remove($plan);
            }

            if ($addNewItem) {
                $nextDescriptionType = max(array_merge([6], array_keys($submittedItems))) + 1;
                $newPlan = new CThematicPlan();
                $newPlan
                    ->setThematic($thematic)
                    ->setDescriptionType($nextDescriptionType)
                    ->setTitle('')
                    ->setDescription('')
                ;
                $thematic->getPlans()->add($newPlan);
                $this->entityManager->persist($newPlan);
            }

            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function sanitizeContent(string $content): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function buildResponse(CThematic $thematic): CourseProgressThematicPlan
    {
        $result = new CourseProgressThematicPlan();
        $result->thematicId = (int) $thematic->getIid();
        $result->thematicTitle = $this->sanitizeContent($thematic->getTitle());
        $result->thematicContent = $this->sanitizeContent((string) $thematic->getContent());
        $result->items = $this->buildItems($thematic);
        $result->csrfToken = (string) $this->csrfTokenManager->getToken(
            CourseProgressThematicPlanProvider::CSRF_TOKEN_ID,
        );
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
                ? $this->sanitizeContent((string) $plan->getDescription())
                : '',
            'defaultTitle' => $defaultTitle,
            'help' => self::DEFAULT_HELP[$descriptionType] ?? '',
            'isCustom' => $isCustom,
            'usesDefaultTitle' => !$plan instanceof CThematicPlan && '' !== $defaultTitle,
        ];
    }
}
