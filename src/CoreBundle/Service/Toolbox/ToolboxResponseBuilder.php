<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Toolbox;

use Chamilo\CoreBundle\ApiResource\Toolbox\ToolboxItem;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CToolbox;
use Chamilo\CourseBundle\Entity\CToolboxVersion;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\RouterInterface;

use const DATE_ATOM;

final readonly class ToolboxResponseBuilder
{
    public function __construct(
        private CLpRepository $learningPathRepository,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
        private ToolboxApplicationService $applicationService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildSummary(
        CToolbox $item,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $user,
        bool $canManage,
    ): array {
        $current = $item->getCurrentVersionEntity();
        $learningPath = $current?->getLearningPath();
        $progress = $learningPath instanceof CLp
            ? ($this->learningPathRepository->lastProgressForUser([$learningPath], $user, $session)[(int) $learningPath->getIid()] ?? 0)
            : 0;
        $latestView = $this->getLatestView($learningPath, $course, $session, $user);
        $score = $this->getLatestScorePercentage($latestView);
        $currentAttempt = max(0, (int) ($latestView?->getViewCount() ?? 0));
        $maxAttempts = $learningPath instanceof CLp ? max(0, $learningPath->getMaxAttempts()) : 0;
        $canRestart = !$canManage
            && $learningPath instanceof CLp
            && !$learningPath->getPreventReinit()
            && $latestView instanceof CLpView
            && (0 === $maxAttempts || $currentAttempt < $maxAttempts);

        return [
            'id' => (int) $item->getIid(),
            'title' => $item->getTitle(),
            'description' => (string) ($item->getDescription() ?? ''),
            'currentVersion' => $item->getCurrentVersion(),
            'versionCount' => $item->getVersions()->count(),
            'visible' => $this->applicationService->isPublished($item, $course, $session, $group),
            'progress' => max(0, min(100, (int) $progress)),
            'score' => $score,
            'learningPathId' => (int) ($learningPath?->getIid() ?? 0),
            'currentAttempt' => $currentAttempt,
            'canRestart' => $canRestart,
            'launchUrl' => $this->buildRuntimeUrl($learningPath, $course, $session, $group, false),
            'previewUrl' => $canManage ? $this->buildRuntimeUrl($learningPath, $course, $session, $group, false, true) : '',
            'reportingUrl' => $canManage ? $this->buildRuntimeUrl($learningPath, $course, $session, $group, true) : '',
            'downloadUrl' => $canManage ? $this->buildDownloadUrl($learningPath, $course, $session, $group) : '',
            'createdAt' => $item->getResourceNode()?->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $item->getResourceNode()?->getUpdatedAt()?->format(DATE_ATOM),
            'canEdit' => $canManage,
            'canGenerate' => $canManage,
        ];
    }

    public function buildItem(
        CToolbox $item,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $user,
        bool $canManage,
    ): ToolboxItem {
        $summary = $this->buildSummary($item, $course, $session, $group, $user, $canManage);

        $result = new ToolboxItem();
        $result->id = $summary['id'];
        $result->title = $summary['title'];
        $result->description = $summary['description'];
        $result->currentVersion = $summary['currentVersion'];
        $result->versionCount = $summary['versionCount'];
        $result->visible = $summary['visible'];
        $result->progress = $summary['progress'];
        $result->score = $summary['score'];
        $result->learningPathId = $summary['learningPathId'];
        $result->currentAttempt = $summary['currentAttempt'];
        $result->canRestart = $summary['canRestart'];
        $result->launchUrl = $summary['launchUrl'];
        $result->previewUrl = $summary['previewUrl'];
        $result->reportingUrl = $summary['reportingUrl'];
        $result->downloadUrl = $summary['downloadUrl'];
        $result->createdAt = $summary['createdAt'];
        $result->updatedAt = $summary['updatedAt'];
        $result->canEdit = $canManage;
        $result->canGenerate = $canManage;

        if ($canManage) {
            foreach ($item->getVersions() as $version) {
                if (!$version instanceof CToolboxVersion) {
                    continue;
                }

                $lp = $version->getLearningPath();
                $creator = $version->getCreatedBy();
                $result->versions[] = [
                    'id' => (int) ($version->getId() ?? 0),
                    'versionNumber' => $version->getVersionNumber(),
                    'title' => $version->getTitle(),
                    'description' => (string) ($version->getDescription() ?? ''),
                    'prompt' => (string) ($version->getPrompt() ?? ''),
                    'provider' => (string) ($version->getProvider() ?? ''),
                    'changeSummary' => (string) ($version->getChangeSummary() ?? ''),
                    'createdAt' => $version->getCreatedAt()->format(DATE_ATOM),
                    'createdBy' => [
                        'id' => (int) ($creator?->getId() ?? 0),
                        'name' => $creator?->getFullName() ?? '',
                    ],
                    'learningPathId' => (int) ($lp?->getIid() ?? 0),
                    'isCurrent' => $version->getVersionNumber() === $item->getCurrentVersion(),
                    'previewUrl' => $this->buildRuntimeUrl($lp, $course, $session, $group, false, true),
                    'reportingUrl' => $this->buildRuntimeUrl($lp, $course, $session, $group, true),
                    'downloadUrl' => $this->buildDownloadUrl($lp, $course, $session, $group),
                ];
            }
        }

        return $result;
    }

    private function getLatestView(
        ?CLp $learningPath,
        Course $course,
        ?Session $session,
        User $user,
    ): ?CLpView {
        if (!$learningPath instanceof CLp) {
            return null;
        }

        /** @var CLpView|null $view */
        return $this->entityManager->getRepository(CLpView::class)->findOneBy(
            [
                'lp' => $learningPath,
                'course' => $course,
                'session' => $session,
                'user' => $user,
            ],
            [
                'viewCount' => 'DESC',
                'iid' => 'DESC',
            ],
        );
    }

    private function getLatestScorePercentage(?CLpView $view): ?float
    {
        if (!$view instanceof CLpView) {
            return null;
        }

        /** @var CLpItemView|null $itemView */
        $itemView = $this->entityManager->getRepository(CLpItemView::class)->findOneBy(
            ['view' => $view],
            [
                'viewCount' => 'DESC',
                'iid' => 'DESC',
            ],
        );
        if (!$itemView instanceof CLpItemView || 'not attempted' === strtolower(trim($itemView->getStatus()))) {
            return null;
        }

        $maxScore = (float) $itemView->getMaxScore();
        if ($maxScore <= 0.0) {
            $maxScore = (float) $itemView->getItem()->getMaxScore();
        }
        if ($maxScore <= 0.0) {
            $maxScore = 100.0;
        }

        return round(max(0.0, min(100.0, 100.0 * (float) $itemView->getScore() / $maxScore)), 2);
    }

    private function buildRuntimeUrl(
        ?CLp $learningPath,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $reporting,
        bool $studentViewFalse = false,
    ): string {
        if (!$learningPath instanceof CLp || (int) $learningPath->getIid() <= 0) {
            return '';
        }

        if ($reporting) {
            return $this->buildReportingUrl($learningPath, $course, $session, $group);
        }

        $params = [
            'cid' => (int) $course->getId(),
            'origin' => 'toolbox',
            'isStudentView' => $studentViewFalse ? 'false' : 'true',
        ];
        if ($session instanceof Session) {
            $params['sid'] = (int) $session->getId();
        }
        if ($group instanceof CGroup) {
            $params['gid'] = (int) $group->getIid();
        }

        return $this->learningPathRepository->getLink($learningPath, $this->router, $params);
    }

    private function buildReportingUrl(
        CLp $learningPath,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): string {
        $courseNodeId = (int) ($learningPath->getResourceNode()?->getParent()?->getId() ?? 0);
        if ($courseNodeId <= 0) {
            return '';
        }

        $query = [
            'cid' => (int) $course->getId(),
            'origin' => 'toolbox',
            'isStudentView' => 'false',
            'returnTo' => 'toolbox',
        ];
        if ($session instanceof Session) {
            $query['sid'] = (int) $session->getId();
        }
        if ($group instanceof CGroup) {
            $query['gid'] = (int) $group->getIid();
        }

        return '/resources/lp/'
            .$courseNodeId.'/'
            .(int) $learningPath->getIid()
            .'/reporting?'
            .http_build_query($query);
    }

    private function buildDownloadUrl(
        ?CLp $learningPath,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): string {
        if (!$learningPath instanceof CLp || (int) $learningPath->getIid() <= 0) {
            return '';
        }

        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($courseNodeId <= 0) {
            return '';
        }

        $query = [
            'cid' => (int) $course->getId(),
            'node' => $courseNodeId,
        ];
        if ($session instanceof Session) {
            $query['sid'] = (int) $session->getId();
        }
        if ($group instanceof CGroup) {
            $query['gid'] = (int) $group->getIid();
        }

        return '/api/learning_paths/'.(int) $learningPath->getIid().'/scorm/package?'.http_build_query($query);
    }
}
