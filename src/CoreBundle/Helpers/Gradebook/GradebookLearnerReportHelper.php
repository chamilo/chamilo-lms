<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers\Gradebook;

use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookLearnerReport;
use Chamilo\CoreBundle\Dto\Gradebook\GradebookContext;
use Chamilo\CoreBundle\Dto\Gradebook\GradebookLearnerReportCriteria;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookComment;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradebookScoreDisplay;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookCommentActionProcessor;
use Chamilo\CoreBundle\State\Gradebook\GradebookContextResolver;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use Chamilo\CoreBundle\State\Gradebook\GradebookScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Builds the detailed per-learner Gradebook report.
 *
 * Shared by the learner report provider and the export controller, which asks
 * for one report per student when it renders the learner summary.
 */
final readonly class GradebookLearnerReportHelper
{
    public function __construct(
        private GradebookContextResolver $contextResolver,
        private GradebookScoreCalculator $scoreCalculator,
        private GradebookLinkResourceResolver $linkResourceResolver,
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function buildReport(GradebookContext $resolved, GradebookLearnerReportCriteria $criteria): GradebookLearnerReport
    {
        $rootCategory = $resolved->rootCategory;
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $category = $criteria->categoryId > 0 && $criteria->categoryId !== (int) $rootCategory->getId()
            ? $this->contextResolver->getCategoryInGradebook(
                $criteria->categoryId,
                $rootCategory,
                $resolved->course,
                $resolved->session,
            )
            : $rootCategory;
        $learnerId = $criteria->userId > 0 ? $criteria->userId : (int) $resolved->user->getId();
        $learner = $this->contextResolver->getStudentInContext(
            $learnerId,
            $resolved->course,
            $resolved->session,
        );

        if (!$resolved->canManage && (int) $resolved->user->getId() !== (int) $learner->getId()) {
            throw new AccessDeniedHttpException('Learners can only view their own Gradebook report.');
        }

        $includeHidden = $resolved->canManage;
        $students = $this->contextResolver->getStudents($resolved->course, $resolved->session);
        $customScoring = $this->contextResolver->isSettingEnabled('gradebook.gradebook_score_display_custom');
        $ranges = $customScoring ? $this->getScoreDisplayRanges($category) : [];
        $upperLimitIncluded = $this->contextResolver->isSettingEnabled('gradebook.gradebook_score_display_upperlimit');

        $resource = new GradebookLearnerReport();
        $resource->context = $this->buildContext($criteria, $resolved->course, $resolved->session, $resolved->groupId);
        $resource->category = $this->normalizeCategory($category);
        $showEmailAddresses = $this->contextResolver->isSettingEnabled('show_email_addresses');
        $resource->learner = $this->normalizeLearner($learner, $showEmailAddresses);
        $resource->canManage = $resolved->canManage;
        $resource->settings = [
            'numberDecimals' => $this->getNumberDecimals(),
            'customScoreDisplay' => $customScoring,
            'hideLinkToItemForStudent' => $this->contextResolver->isSettingEnabled('gradebook.gradebook_hide_link_to_item_for_student'),
            'allowComments' => $this->contextResolver->isSettingEnabled('gradebook.allow_gradebook_comments'),
            'allowSkillRelItems' => $this->contextResolver->isSettingEnabled('skill.allow_skill_rel_items'),
            'hidePdfReportButton' => $this->contextResolver->isSettingEnabled('gradebook.gradebook_hide_pdf_report_button'),
            'showEmailAddresses' => $showEmailAddresses,
        ];

        foreach ($this->getItemsRecursive($category, $resolved->course, $resolved->session, $includeHidden) as $item) {
            $result = $this->calculateItem($item, $learner, $resolved->course, $resolved->session);
            $average = $this->calculateAverage($item, $students, $resolved->course, $resolved->session);
            $itemCategory = $item->getCategory();
            $title = '';
            $url = null;
            $kind = 'evaluation';

            if ($item instanceof GradebookEvaluation) {
                $title = (string) $item->getTitle();
            } else {
                $kind = 'link';
                $normalizedLink = $this->linkResourceResolver->normalizeLink(
                    $item,
                    $resolved->course,
                    $resolved->session,
                    $resolved->groupId,
                    $resolved->canManage,
                );
                $title = (string) ($normalizedLink['title'] ?? '');
                $url = \is_string($normalizedLink['url'] ?? null) ? $normalizedLink['url'] : null;
            }

            if (!$resolved->canManage && $resource->settings['hideLinkToItemForStudent']) {
                $url = null;
            }

            $resource->rows[] = [
                'id' => (int) $item->getId(),
                'kind' => $kind,
                'title' => $title,
                'courseTitle' => $resolved->course->getTitle(),
                'categoryId' => (int) $itemCategory->getId(),
                'categoryTitle' => $itemCategory->getTitle(),
                'score' => $result['score'],
                'maxScore' => $result['maxScore'],
                'percentage' => $result['percentage'],
                'averageScore' => $average['score'],
                'averageMaxScore' => $average['maxScore'],
                'ranking' => $customScoring
                    ? $this->resolveScoreDisplay($result['percentage'], $ranges, $upperLimitIncluded)
                    : null,
                'url' => $url,
                'hasResult' => $result['hasResult'],
            ];
        }

        usort(
            $resource->rows,
            static fn (array $left, array $right): int => strnatcasecmp((string) $left['title'], (string) $right['title']),
        );

        $resource->total = $this->scoreCalculator->calculateCategory(
            $category,
            $learner,
            $resolved->course,
            $resolved->session,
            $includeHidden,
        );

        if ($resolved->canManage && $resource->settings['allowComments']) {
            $comment = $this->entityManager->getRepository(GradebookComment::class)->findOneBy([
                'gradeBook' => $category,
                'user' => $learner,
            ]);
            if ($comment instanceof GradebookComment) {
                $resource->comment = (string) ($comment->getComment() ?? '');
            }
            $resource->commentCsrfToken = (string) $this->csrfTokenManager->getToken(
                GradebookCommentActionProcessor::CSRF_TOKEN_ID,
            );
        }

        return $resource;
    }

    /**
     * @return list<GradebookEvaluation|GradebookLink>
     */
    private function getItemsRecursive(
        GradebookCategory $category,
        Course $course,
        ?Session $session,
        bool $includeHidden,
    ): array {
        $items = [];

        foreach ($category->getEvaluations() as $evaluation) {
            if (!$evaluation instanceof GradebookEvaluation
                || (int) $evaluation->getCourse()->getId() !== (int) $course->getId()
                || (!$includeHidden && 1 !== (int) $evaluation->getVisible())
            ) {
                continue;
            }

            $items[] = $evaluation;
        }

        foreach ($category->getLinks() as $link) {
            if (!$link instanceof GradebookLink
                || (int) $link->getCourse()->getId() !== (int) $course->getId()
                || (!$includeHidden && 1 !== (int) $link->getVisible())
            ) {
                continue;
            }

            $items[] = $link;
        }

        foreach ($category->getSubCategories() as $subCategory) {
            if (!$subCategory instanceof GradebookCategory
                || !$this->sameCategoryContext($subCategory, $course, $session)
                || (!$includeHidden && !$subCategory->getVisible())
            ) {
                continue;
            }

            array_push($items, ...$this->getItemsRecursive($subCategory, $course, $session, $includeHidden));
        }

        return $items;
    }

    /**
     * @return array{score: float|null, maxScore: float|null, percentage: float|null, attempts: int, date: string|null, weightedScore: float|null, weight: float, hasResult: bool}
     */
    private function calculateItem(
        GradebookEvaluation|GradebookLink $item,
        User $learner,
        Course $course,
        ?Session $session,
    ): array {
        if ($item instanceof GradebookEvaluation) {
            return $this->scoreCalculator->calculateEvaluation($item, $learner);
        }

        return $this->scoreCalculator->calculateLink($item, $learner, $course, $session);
    }

    /**
     * @param list<User> $students
     *
     * @return array{score: float|null, maxScore: float|null}
     */
    private function calculateAverage(
        GradebookEvaluation|GradebookLink $item,
        array $students,
        Course $course,
        ?Session $session,
    ): array {
        $sumPercent = 0.0;
        $count = 0;
        $maxScore = null;

        foreach ($students as $student) {
            $result = $this->calculateItem($item, $student, $course, $session);
            if (!$result['hasResult'] || null === $result['score'] || null === $result['maxScore'] || 0.0 === (float) $result['maxScore']) {
                continue;
            }

            $sumPercent += (float) $result['score'] / (float) $result['maxScore'];
            $maxScore ??= (float) $result['maxScore'];
            ++$count;
        }

        if (0 === $count || null === $maxScore) {
            return ['score' => null, 'maxScore' => $maxScore];
        }

        return [
            'score' => ($sumPercent / $count) * $maxScore,
            'maxScore' => $maxScore,
        ];
    }

    /**
     * @return list<array{score: float, display: string}>
     */
    private function getScoreDisplayRanges(GradebookCategory $category): array
    {
        $rows = $this->entityManager->getRepository(GradebookScoreDisplay::class)->findBy(
            ['category' => $category],
            ['score' => 'ASC'],
        );
        $ranges = [];
        foreach ($rows as $row) {
            if (!$row instanceof GradebookScoreDisplay) {
                continue;
            }

            $ranges[] = [
                'score' => (float) $row->getScore(),
                'display' => (string) $row->getDisplay(),
            ];
        }

        return $ranges;
    }

    /**
     * @param list<array{score: float, display: string}> $ranges
     */
    private function resolveScoreDisplay(?float $percentage, array $ranges, bool $upperLimitIncluded): ?string
    {
        if (null === $percentage || [] === $ranges) {
            return null;
        }

        $score = max(0.0, min(100.0, $percentage));
        foreach ($ranges as $range) {
            $limit = $range['score'];
            if (($upperLimitIncluded && $score <= $limit)
                || (!$upperLimitIncluded && ($score < $limit || 100.0 === $limit))
            ) {
                return $range['display'];
            }
        }

        return $ranges[array_key_last($ranges)]['display'];
    }

    /**
     * @return array<string, int>
     */
    private function buildContext(
        GradebookLearnerReportCriteria $criteria,
        Course $course,
        ?Session $session,
        int $groupId,
    ): array {
        return [
            'cid' => (int) $course->getId(),
            'sid' => (int) ($session?->getId() ?? 0),
            'gid' => $groupId,
            'node' => $criteria->node,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCategory(GradebookCategory $category): array
    {
        return [
            'id' => (int) $category->getId(),
            'title' => $category->getTitle(),
            'calculationMode' => $category->getCalculationMode()->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeLearner(User $learner, bool $showEmailAddresses): array
    {
        return [
            'id' => (int) $learner->getId(),
            'fullName' => $learner->getFullName(),
            'username' => $learner->getUsername(),
            'firstName' => (string) $learner->getFirstname(),
            'lastName' => (string) $learner->getLastname(),
            'officialCode' => (string) ($learner->getOfficialCode() ?? ''),
            'email' => $showEmailAddresses ? (string) ($learner->getEmail() ?? '') : '',
        ];
    }

    private function getNumberDecimals(): int
    {
        $value = $this->settingsManager->getSetting('gradebook.gradebook_number_decimals');
        if (null === $value || '' === $value) {
            $value = $this->settingsManager->getSetting('gradebook_number_decimals');
        }

        return max(0, min(6, (int) ($value ?? 2)));
    }

    private function sameCategoryContext(GradebookCategory $category, Course $course, ?Session $session): bool
    {
        if ((int) $category->getCourse()->getId() !== (int) $course->getId()) {
            return false;
        }

        $categorySessionId = (int) ($category->getSession()?->getId() ?? 0);
        $sessionId = (int) ($session?->getId() ?? 0);

        return $categorySessionId === $sessionId;
    }
}
