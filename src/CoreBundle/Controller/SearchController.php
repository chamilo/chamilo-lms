<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Search\Xapian\XapianIndexService;
use Chamilo\CoreBundle\Search\Xapian\XapianSearchService;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class SearchController extends AbstractController
{
    public function __construct(
        private readonly XapianSearchService $xapianSearchService,
        private readonly XapianIndexService $xapianIndexService,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Minimal Xapian search endpoint returning JSON.
     *
     * Example: /search/xapian?q=test
     */
    #[Route(
        path: '/search/xapian',
        name: 'chamilo_core.search_xapian',
        methods: ['GET']
    )]
    public function xapianSearchAction(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));

        if ('' === $q) {
            return $this->json([
                'query' => '',
                'total' => 0,
                'results' => [],
            ]);
        }

        try {
            $result = $this->xapianSearchService->search(
                queryString: $q,
                offset: 0,
                length: 20,
            );

            return $this->json([
                'query' => $q,
                'total' => $result['count'],
                'results' => $result['results'],
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'query' => $q,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * HTML search page using Xapian.
     *
     * Example: /search/xapian/ui?q=test
     */
    #[Route(
        path: '/search/xapian/ui',
        name: 'chamilo_core.search_xapian_ui',
        methods: ['GET']
    )]
    public function xapianSearchPageAction(Request $request): Response
    {
        $q = trim((string) $request->query->get('q', ''));

        $total = 0;
        $results = [];
        $error = null;

        if ('' !== $q) {
            try {
                $searchResult = $this->xapianSearchService->search(
                    queryString: $q,
                    offset: 0,
                    length: 20
                );

                $total = $searchResult['count'] ?? 0;
                $results = $searchResult['results'] ?? [];

                $results = $this->hydrateResultsWithCourseRootNode($results);
                $results = $this->hydrateQuestionResultsWithQuizIds($results);
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('@ChamiloCore/Search/xapian_search.html.twig', [
            'query' => $q,
            'total' => $total,
            'results' => $results,
            'error' => $error,
        ]);
    }

    /**
     * Demo endpoint: index a sample document into Xapian.
     *
     * Call this once, then query /search/xapian?q=demo or ?q=chamilo.
     */
    #[Route(
        path: '/search/xapian/demo-index',
        name: 'chamilo_core.search_xapian_demo_index',
        methods: ['POST']
    )]
    public function xapianDemoIndexAction(): JsonResponse
    {
        try {
            $docId = $this->xapianIndexService->indexDemoDocument();

            return $this->json([
                'indexed' => true,
                'doc_id' => $docId,
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'indexed' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach course_root_node_id to each result if we can resolve it from course_id.
     *
     * @param array<int, array<string, mixed>> $results
     *
     * @return array<int, array<string, mixed>>
     */
    private function hydrateResultsWithCourseRootNode(array $results): array
    {
        foreach ($results as &$result) {
            if (!\is_array($result)) {
                continue;
            }

            $data = $result['data'] ?? [];
            if (!\is_array($data)) {
                $data = [];
            }

            // If the field already exists (coming from the indexer), keep it.
            if (!empty($data['course_root_node_id'])) {
                $result['data'] = $data;

                continue;
            }

            $courseId = isset($data['course_id']) && '' !== $data['course_id']
                ? (int) $data['course_id']
                : null;

            if (!$courseId) {
                $result['data'] = $data;

                continue;
            }

            /** @var Course|null $course */
            $course = $this->em->find(Course::class, $courseId);
            if (!$course || !$course->getResourceNode()) {
                $result['data'] = $data;

                continue;
            }

            $data['course_root_node_id'] = (string) $course->getResourceNode()->getId();
            $result['data'] = $data;
        }

        return $results;
    }

    /**
     * For question results, resolve the related quiz from c_quiz_rel_question
     * and attach quiz_id into the result data so the Twig can build the link.
     *
     * @param array<int, array<string, mixed>> $results
     *
     * @return array<int, array<string, mixed>>
     */
    private function hydrateQuestionResultsWithQuizIds(array $results): array
    {
        foreach ($results as &$result) {
            if (!\is_array($result)) {
                continue;
            }

            $data = $result['data'] ?? [];
            if (!\is_array($data)) {
                $data = [];
            }

            $kind = $data['kind'] ?? null;
            $tool = $data['tool'] ?? null;

            $isQuestion =
                ('question' === $kind)
                || ('quiz_question' === $tool);

            if (!$isQuestion) {
                $result['data'] = $data;

                continue;
            }

            if (!empty($data['quiz_id'])) {
                $result['data'] = $data;

                continue;
            }

            $questionId = isset($data['question_id']) && '' !== $data['question_id']
                ? (int) $data['question_id']
                : null;

            if (null === $questionId) {
                $result['data'] = $data;

                continue;
            }

            /** @var CQuizRelQuestion|null $rel */
            $rel = $this->em
                ->getRepository(CQuizRelQuestion::class)
                ->findOneBy(['question' => $questionId])
            ;

            if (!$rel) {
                $result['data'] = $data;

                continue;
            }

            $quiz = $rel->getQuiz();
            if (!$quiz || null === $quiz->getIid()) {
                $result['data'] = $data;

                continue;
            }

            $data['quiz_id'] = (string) $quiz->getIid();

            $result['data'] = $data;
        }

        return $results;
    }
}
