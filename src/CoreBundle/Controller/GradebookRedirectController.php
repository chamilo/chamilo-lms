<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class GradebookRedirectController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/gradebook/redirect', name: 'gradebook_redirect', methods: ['GET'])]
    public function __invoke(Request $request): RedirectResponse
    {
        $courseId = $this->firstPositiveInt($request, ['cid', 'courseId']);
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course || null === $course->getResourceNode()?->getId()) {
            throw new NotFoundHttpException('The requested course was not found.');
        }

        $sessionId = $this->firstPositiveInt($request, ['sid', 'id_session']);
        if ($sessionId > 0) {
            $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
            if (!$session instanceof Session || !$session->hasCourse($course)) {
                throw new NotFoundHttpException('The requested course session was not found.');
            }
        }

        $groupId = max(0, $this->firstPositiveInt($request, ['gid', 'gidReq']));
        $categoryId = $this->firstPositiveInt($request, ['categoryId', 'selectcat', 'cat_id', 'cat_code']);
        $userId = $this->firstPositiveInt($request, ['userId', 'user_id', 'user']);
        $evaluationId = $this->firstPositiveInt($request, ['evaluationId', 'selecteval', 'visiblelog']);
        $itemId = $this->firstPositiveInt($request, ['itemId', 'visiblelink', 'visiblelog']);
        $kind = strtolower(trim((string) $request->query->get('kind', '')));
        $view = strtolower(trim((string) $request->query->get('view', 'overview')));
        $node = (int) $course->getResourceNode()->getId();

        $path = match ($view) {
            'flat' => '/resources/gradebook/'.$node.'/reports/list',
            'students', 'summary' => '/resources/gradebook/'.$node.'/reports/students',
            'learner', 'personal' => '/resources/gradebook/'.$node.'/learners'.($userId > 0 ? '/'.$userId : ''),
            'weights' => '/resources/gradebook/'.$node.'/weights',
            'scoring' => '/resources/gradebook/'.$node.'/scoring',
            'graph', 'statistics' => '/resources/gradebook/'.$node.'/reports/graph',
            'certificates' => '/resources/gradebook/'.$node.'/certificates',
            'evaluation-results', 'result' => $this->requireIdPath(
                $evaluationId,
                '/resources/gradebook/'.$node.'/evaluations/%d/results',
                'A valid evaluation id is required.',
            ),
            'evaluation-statistics' => $this->requireIdPath(
                $evaluationId,
                '/resources/gradebook/'.$node.'/evaluations/%d/statistics',
                'A valid evaluation id is required.',
            ),
            'history' => $this->historyPath($node, $kind, $itemId),
            'skills' => $this->requireIdPath(
                $userId,
                '/resources/gradebook/'.$node.'/learners/%d/skills',
                'A valid learner id is required.',
            ),
            'badges' => '/resources/gradebook/'.$node.'/learners'.($userId > 0 ? '/'.$userId : '').'/badges',
            default => '/resources/gradebook/'.$node.'/',
        };

        $query = [
            'cid' => $courseId,
            'sid' => $sessionId,
            'gid' => $groupId,
        ];
        if ($categoryId > 0) {
            $query['categoryId'] = $categoryId;
        }

        return new RedirectResponse($path.'?'.http_build_query($query));
    }

    /**
     * @param list<string> $keys
     */
    private function firstPositiveInt(Request $request, array $keys): int
    {
        foreach ($keys as $key) {
            $value = $request->query->getInt($key);
            if ($value > 0) {
                return $value;
            }
        }

        return 0;
    }

    private function requireIdPath(int $id, string $format, string $error): string
    {
        if ($id <= 0) {
            throw new BadRequestHttpException($error);
        }

        return \sprintf($format, $id);
    }

    private function historyPath(int $node, string $kind, int $itemId): string
    {
        if (!\in_array($kind, ['evaluation', 'link'], true) || $itemId <= 0) {
            throw new BadRequestHttpException('A valid Gradebook history item is required.');
        }

        return '/resources/gradebook/'.$node.'/history/'.$kind.'/'.$itemId;
    }
}
