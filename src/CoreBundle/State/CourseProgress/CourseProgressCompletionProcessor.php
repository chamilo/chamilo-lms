<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseProgress\CourseProgressCompletion;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Repository\CThematicAdvanceRepository;
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

/**
 * @implements ProcessorInterface<CourseProgressCompletion, CourseProgressCompletion>
 */
final readonly class CourseProgressCompletionProcessor implements ProcessorInterface
{
    use CourseProgressAccessHelperTrait;

    public const string CSRF_TOKEN_ID = 'course_progress_completion';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CThematicRepository $thematicRepository,
        private CThematicAdvanceRepository $thematicAdvanceRepository,
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
    ): CourseProgressCompletion {
        if (!$data instanceof CourseProgressCompletion) {
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

        $advance = $this->getTargetAdvance($data->advanceId, $course, $session);
        $orderedAdvances = $this->getWritableOrderedAdvances($course, $session);
        $targetAdvanceId = (int) $advance->getIid();

        $targetIsWritable = false;

        foreach ($orderedAdvances as $orderedAdvance) {
            if ($orderedAdvance->getIid() === $advance->getIid()) {
                $targetIsWritable = true;

                break;
            }
        }

        if (!$targetIsWritable) {
            throw new AccessDeniedHttpException('The requested thematic advance is not editable in this context.');
        }

        $isDone = true;
        foreach ($orderedAdvances as $orderedAdvance) {
            $orderedAdvance->setDoneAdvance($isDone);
            $this->entityManager->persist($orderedAdvance);

            if ($orderedAdvance->getIid() === $advance->getIid()) {
                $isDone = false;
            }
        }

        $this->entityManager->flush();

        $result = new CourseProgressCompletion();
        $result->advanceId = $targetAdvanceId;
        $result->doneAdvanceIds = $this->getDoneAdvanceIds($course, $session);
        $result->totalAverage = $this->thematicRepository->calculateTotalAverageForCourse($course, $session);

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

        throw new AccessDeniedHttpException('You are not allowed to update course progress in this context.');
    }

    private function getTargetAdvance(int $advanceId, Course $course, ?Session $session): CThematicAdvance
    {
        if ($advanceId <= 0) {
            throw new BadRequestHttpException('A valid thematic advance id is required.');
        }

        $advance = $this->thematicAdvanceRepository->find($advanceId);
        if (!$advance instanceof CThematicAdvance) {
            throw new NotFoundHttpException('The requested thematic advance was not found.');
        }

        $thematic = $advance->getThematic();
        if (!$this->thematicBelongsToExactContext($thematic, $course, $session)) {
            throw new AccessDeniedHttpException('The requested thematic advance does not belong to the current course context.');
        }

        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to update this thematic advance.');
        }

        return $advance;
    }

    /**
     * @return CThematicAdvance[]
     */
    private function getWritableOrderedAdvances(Course $course, ?Session $session): array
    {
        $orderedAdvances = [];

        foreach ($this->thematicRepository->findOrderedAdvancesForCourse($course, $session) as $advance) {
            $thematic = $advance->getThematic();

            if (!$this->thematicBelongsToExactContext($thematic, $course, $session)) {
                continue;
            }

            $orderedAdvances[] = $advance;
        }

        return $orderedAdvances;
    }

    /**
     * @return int[]
     */
    private function getDoneAdvanceIds(Course $course, ?Session $session): array
    {
        $doneAdvanceIds = [];

        foreach ($this->thematicRepository->getThematicListForCourse($course, $session) as $thematic) {
            if (!$thematic instanceof CThematic) {
                continue;
            }

            foreach ($thematic->getAdvances() as $advance) {
                if (!$advance instanceof CThematicAdvance
                    || true !== $advance->getDoneAdvance()
                    || null === $advance->getIid()
                ) {
                    continue;
                }

                $doneAdvanceIds[] = (int) $advance->getIid();
            }
        }

        return $doneAdvanceIds;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }
}
