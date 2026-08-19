<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookWeightAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradebookLinkevalLog;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<GradebookWeightAction, GradebookWeightAction>
 */
final readonly class GradebookWeightActionProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_weight_action';

    private const ACTION_SAVE = 'save';
    private const ACTION_AUTO_DISTRIBUTE = 'auto_distribute';

    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookLinkResourceResolver $linkResourceResolver,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookWeightAction
    {
        if (!$data instanceof GradebookWeightAction) {
            throw new BadRequestHttpException('Invalid Gradebook weight action payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        $resolved = $this->contextResolver->resolve($request, true);
        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $categoryId = (int) ($data->categoryId ?? 0);
        $category = $categoryId > 0
            ? $this->contextResolver->getCategoryInGradebook($categoryId, $rootCategory, $resolved['course'], $resolved['session'])
            : $rootCategory;

        if (null !== $category->getGradeModel()) {
            throw new AccessDeniedHttpException('Weights cannot be edited manually when a Gradebook model is active.');
        }
        if (1 === (int) $category->getLocked()) {
            throw new AccessDeniedHttpException('The Gradebook category is locked.');
        }

        $action = strtolower(trim($data->action));
        if (self::ACTION_AUTO_DISTRIBUTE === $action) {
            $this->autoDistribute($category, $resolved['course'], $resolved['session'], $resolved['user']);
        } elseif (self::ACTION_SAVE === $action) {
            $this->saveWeights($category, $data->weights, $resolved['course'], $resolved['session'], $resolved['user']);
        } else {
            throw new BadRequestHttpException('Unsupported Gradebook weight action.');
        }

        $this->entityManager->flush();

        $response = new GradebookWeightAction();
        $response->action = $action;
        $response->success = true;

        return $response;
    }

    private function autoDistribute(GradebookCategory $category, Course $course, ?Session $session, User $user): void
    {
        $categoryLinks = $category->getLinks();
        $categoryEvaluations = $category->getEvaluations();
        $links = array_values(array_filter(
            \is_array($categoryLinks) ? $categoryLinks : $categoryLinks->toArray(),
            static fn (mixed $item): bool => $item instanceof GradebookLink,
        ));
        $evaluations = array_values(array_filter(
            \is_array($categoryEvaluations) ? $categoryEvaluations : $categoryEvaluations->toArray(),
            static fn (mixed $item): bool => $item instanceof GradebookEvaluation,
        ));
        $itemCount = \count($links) + \count($evaluations);
        if (0 === $itemCount) {
            return;
        }

        $expectedTotal = (float) $category->getWeight();
        $weight = round($expectedTotal / $itemCount, 2);
        $computedTotal = $weight * $itemCount;
        $diff = $computedTotal > $expectedTotal ? $computedTotal - $expectedTotal : 0.0;
        $diffApplied = false;

        foreach ($links as $link) {
            $weightToApply = $weight;
            if (!$diffApplied && $diff > 0.0) {
                $weightToApply -= $diff;
                $diffApplied = true;
            }
            $this->updateLinkWeight($link, $weightToApply, $course, $session, $user);
        }

        foreach ($evaluations as $evaluation) {
            $weightToApply = $weight;
            if (!$diffApplied && $diff > 0.0) {
                $weightToApply -= $diff;
                $diffApplied = true;
            }
            $this->updateEvaluationWeight($evaluation, $weightToApply, $user);
        }
    }

    /**
     * @param list<array<string, mixed>> $weights
     */
    private function saveWeights(
        GradebookCategory $category,
        array $weights,
        Course $course,
        ?Session $session,
        User $user,
    ): void {
        foreach ($weights as $row) {
            $kind = strtolower(trim((string) ($row['kind'] ?? '')));
            $id = (int) ($row['id'] ?? 0);
            $weight = $row['weight'] ?? null;
            if ($id <= 0 || !is_numeric($weight) || (float) $weight < 0.0) {
                throw new BadRequestHttpException('Every Gradebook weight must contain a valid item id and a non-negative numeric weight.');
            }

            if ('link' === $kind) {
                $link = $this->entityManager->getRepository(GradebookLink::class)->find($id);
                if (!$link instanceof GradebookLink || $link->getCategory() !== $category) {
                    throw new AccessDeniedHttpException('A Gradebook online activity is outside the selected category.');
                }
                if (1 === (int) $link->getLocked()) {
                    throw new AccessDeniedHttpException('A Gradebook online activity is locked.');
                }
                $this->updateLinkWeight($link, (float) $weight, $course, $session, $user);

                continue;
            }

            if ('evaluation' === $kind) {
                $evaluation = $this->entityManager->getRepository(GradebookEvaluation::class)->find($id);
                if (!$evaluation instanceof GradebookEvaluation || $evaluation->getCategory() !== $category) {
                    throw new AccessDeniedHttpException('A manual evaluation is outside the selected category.');
                }
                if (1 === (int) $evaluation->getLocked()) {
                    throw new AccessDeniedHttpException('A manual evaluation is locked.');
                }
                $this->updateEvaluationWeight($evaluation, (float) $weight, $user);

                continue;
            }

            throw new BadRequestHttpException('Unsupported Gradebook weight item type.');
        }
    }

    private function updateLinkWeight(
        GradebookLink $link,
        float $weight,
        Course $course,
        ?Session $session,
        User $user,
    ): void {
        $summary = $this->linkResourceResolver->normalizeLink($link, $course, $session, 0, true);
        $this->logLink($link, (string) ($summary['title'] ?? $course->getTitle()), $user);
        $link->setWeight($weight);

        try {
            $resource = $this->linkResourceResolver->requireResource(
                (int) $link->getType(),
                (int) $link->getRefId(),
                $course,
                $session,
            );
        } catch (AccessDeniedHttpException|BadRequestHttpException|NotFoundHttpException) {
            return;
        }

        $type = (int) $link->getType();
        if (GradebookLinkResourceResolver::LINK_ATTENDANCE === $type && $resource instanceof CAttendance) {
            $resource->setAttendanceWeight($weight);
        } elseif (GradebookLinkResourceResolver::LINK_FORUM_THREAD === $type && $resource instanceof CForumThread) {
            $resource->setThreadWeight($weight);
        } elseif (GradebookLinkResourceResolver::LINK_STUDENT_PUBLICATION === $type && $resource instanceof CStudentPublication) {
            $resource->setWeight($weight);
        }
    }

    private function updateEvaluationWeight(GradebookEvaluation $evaluation, float $weight, User $user): void
    {
        $this->logEvaluation($evaluation, $user);
        $evaluation->setWeight($weight);
    }

    private function logLink(GradebookLink $link, string $title, User $user): void
    {
        $log = new GradebookLinkevalLog();
        $log
            ->setIdLinkevalLog((int) $link->getId())
            ->setTitle($title)
            ->setDescription('')
            ->setWeight((int) round((float) $link->getWeight()))
            ->setVisible(1 === (int) $link->getVisible())
            ->setType('link')
            ->setUser($user)
            ->setCreatedAt(new DateTime())
        ;
        $this->entityManager->persist($log);
    }

    private function logEvaluation(GradebookEvaluation $evaluation, User $user): void
    {
        $log = new GradebookLinkevalLog();
        $log
            ->setIdLinkevalLog((int) $evaluation->getId())
            ->setTitle((string) $evaluation->getTitle())
            ->setDescription((string) $evaluation->getDescription())
            ->setWeight((int) round((float) $evaluation->getWeight()))
            ->setVisible(1 === (int) $evaluation->getVisible())
            ->setType('evaluation')
            ->setUser($user)
            ->setCreatedAt(new DateTime())
        ;
        $this->entityManager->persist($log);
    }

    private function validateCsrfToken(string $submittedCsrfToken): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedCsrfToken))) {
            throw new AccessDeniedHttpException('The CSRF token is invalid.');
        }
    }
}
