<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookScoringAction;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookScoreDisplay;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<GradebookScoringAction, GradebookScoringAction>
 */
final readonly class GradebookScoringActionProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_scoring_action';

    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookScoringAction
    {
        if (!$data instanceof GradebookScoringAction) {
            throw new BadRequestHttpException('Invalid Gradebook scoring payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        $resolved = $this->contextResolver->resolve($request, true);
        if (!$this->contextResolver->isSettingEnabled('gradebook.teachers_can_change_score_settings')) {
            throw new AccessDeniedHttpException('Teachers cannot change Gradebook score settings on this platform.');
        }
        if (!$this->contextResolver->isSettingEnabled('gradebook.gradebook_score_display_custom')) {
            throw new AccessDeniedHttpException('Custom Gradebook score display is disabled on this platform.');
        }

        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $categoryId = (int) ($data->categoryId ?? 0);
        $category = $categoryId > 0
            ? $this->contextResolver->getCategoryInGradebook($categoryId, $rootCategory, $resolved['course'], $resolved['session'])
            : $rootCategory;

        $ranges = $this->normalizeRanges($data->ranges);
        if ([] === $ranges) {
            $response = new GradebookScoringAction();
            $response->success = true;

            return $response;
        }

        $colorSplit = 0;
        if ($this->contextResolver->isSettingEnabled('gradebook.my_display_coloring')) {
            $colorSplit = (int) ($data->colorSplitPercent ?? 0);
            if ($colorSplit <= 0 || $colorSplit > 100) {
                throw new BadRequestHttpException('The score color threshold must be greater than 0 and at most 100.');
            }
        }

        $existing = $this->entityManager->getRepository(GradebookScoreDisplay::class)->findBy(['category' => $category]);
        foreach ($existing as $row) {
            if ($row instanceof GradebookScoreDisplay) {
                $this->entityManager->remove($row);
            }
        }

        foreach ($ranges as $range) {
            $row = new GradebookScoreDisplay();
            $row
                ->setCategory($category)
                ->setScore($range['score'])
                ->setDisplay($range['display'])
                ->setScoreColorPercent((float) $colorSplit)
            ;
            $this->entityManager->persist($row);
        }

        $this->entityManager->flush();

        $response = new GradebookScoringAction();
        $response->success = true;

        return $response;
    }

    /**
     * @param list<array<string, mixed>> $ranges
     *
     * @return list<array{score: float, display: string}>
     */
    private function normalizeRanges(array $ranges): array
    {
        if (\count($ranges) > 20) {
            throw new BadRequestHttpException('A maximum of 20 Gradebook score ranges is allowed.');
        }

        $normalized = [];
        $seen = [];
        foreach ($ranges as $range) {
            $rawScore = $range['score'] ?? null;
            $display = trim((string) ($range['display'] ?? ''));
            if (null === $rawScore || '' === (string) $rawScore) {
                continue;
            }
            if (!is_numeric($rawScore)) {
                throw new BadRequestHttpException('Every score range boundary must be numeric.');
            }

            $score = (float) $rawScore;
            if ($score <= 0.0 || $score > 100.0) {
                throw new BadRequestHttpException('Every score range boundary must be greater than 0 and at most 100.');
            }
            if (mb_strlen($display) > 40) {
                throw new BadRequestHttpException('A score range label cannot exceed 40 characters.');
            }

            $key = (string) $score;
            if (isset($seen[$key])) {
                throw new BadRequestHttpException('There is no unique score range possibility.');
            }
            $seen[$key] = true;
            $normalized[] = ['score' => $score, 'display' => $display];
        }

        usort($normalized, static fn (array $left, array $right): int => $left['score'] <=> $right['score']);

        return $normalized;
    }

    private function validateCsrfToken(string $submittedCsrfToken): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedCsrfToken))) {
            throw new AccessDeniedHttpException('The CSRF token is invalid.');
        }
    }
}
