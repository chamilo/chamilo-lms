<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\SearchEngineRef;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Handles Xapian indexing for quizzes (exercises).
 */
final class QuizXapianIndexer
{
    public function __construct(
        private readonly XapianIndexService $xapianIndexService,
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settingsManager,
    ) {
    }

    /**
     * Index or reindex a quiz.
     *
     * @return int|null Xapian document id or null when indexing is skipped
     */
    public function indexQuiz(CQuiz $quiz): ?int
    {
        $resourceNode = $quiz->getResourceNode();

        error_log('[Xapian] indexQuiz: start for quiz id='.(string) $quiz->getIid()
            .', resource_node_id='.($resourceNode ? $resourceNode->getId() : 'null')
        );

        // Check global setting
        $enabled = (string) $this->settingsManager->getSetting('search.search_enabled', true);
        if ($enabled !== 'true') {
            error_log('[Xapian] indexQuiz: search is disabled, skipping');
            return null;
        }

        if (!$resourceNode instanceof ResourceNode) {
            error_log('[Xapian] indexQuiz: missing ResourceNode, skipping');
            return null;
        }

        // Resolve course and session from resource links
        [$courseId, $sessionId] = $this->resolveCourseAndSession($resourceNode);

        $title       = (string) $quiz->getTitle();
        $description = (string) ($quiz->getDescription() ?? '');

        // For now: description only. If later you implement "specific fields"
        // for quizzes in C2, you can concatenate them here like in v1.
        $content = \trim($description);

        // Data that will appear in search results under "data"
        $fields = [
            'kind'             => 'quiz',
            'tool'             => 'quiz',
            'title'            => $title,
            'description'      => $description,
            'content'          => $content,
            'resource_node_id' => (string) $resourceNode->getId(),
            'quiz_id'          => (string) $quiz->getIid(),
            'course_id'        => $courseId !== null ? (string) $courseId : '',
            'session_id'       => $sessionId !== null ? (string) $sessionId : '',
            // Legacy-like metadata, if you want them later:
            'xapian_data'      => json_encode([
                'type'        => 'exercise',
                'exercise_id' => (int) $quiz->getIid(),
                'course_id'   => $courseId,
            ]),
        ];

        // Terms: allow filtering by kind, course, session
        $terms = ['Tquiz'];
        if ($courseId !== null) {
            $terms[] = 'C'.$courseId;
        }
        if ($sessionId !== null) {
            $terms[] = 'S'.$sessionId;
        }

        // Look for existing SearchEngineRef for this resource node
        /** @var SearchEngineRef|null $existingRef */
        $existingRef = $this->em
            ->getRepository(SearchEngineRef::class)
            ->findOneBy(['resourceNodeId' => $resourceNode->getId()]);

        $existingDocId = $existingRef?->getSearchDid();

        if ($existingDocId !== null) {
            try {
                $this->xapianIndexService->deleteDocument($existingDocId);
                error_log('[Xapian] indexQuiz: deleted previous docId='.(string) $existingDocId);
            } catch (\Throwable $e) {
                error_log('[Xapian] indexQuiz: failed to delete previous docId='
                    .(string) $existingDocId.' error='.$e->getMessage()
                );
            }
        }

        // Index in Xapian
        try {
            $docId = $this->xapianIndexService->indexDocument($fields, $terms);
        } catch (\Throwable $e) {
            error_log('[Xapian] indexQuiz: indexDocument() failed: '.$e->getMessage());
            return null;
        }

        // Update mapping
        if ($existingRef instanceof SearchEngineRef) {
            $existingRef->setSearchDid($docId);
        } else {
            $existingRef = new SearchEngineRef();
            $existingRef->setResourceNodeId((int) $resourceNode->getId());
            $existingRef->setSearchDid($docId);
            $this->em->persist($existingRef);
        }

        $this->em->flush();

        error_log('[Xapian] indexQuiz: completed with docId='.(string) $docId
            .', search_engine_ref_id='.$existingRef->getId()
        );

        return $docId;
    }

    /**
     * Delete quiz index (called on entity removal).
     */
    public function deleteQuizIndex(CQuiz $quiz): void
    {
        $resourceNode = $quiz->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return;
        }

        /** @var SearchEngineRef|null $ref */
        $ref = $this->em
            ->getRepository(SearchEngineRef::class)
            ->findOneBy(['resourceNodeId' => $resourceNode->getId()]);

        if (!$ref) {
            return;
        }

        try {
            $this->xapianIndexService->deleteDocument($ref->getSearchDid());
        } catch (\Throwable $e) {
            error_log('[Xapian] deleteQuizIndex: deleteDocument failed: '.$e->getMessage());
        }

        $this->em->remove($ref);
        $this->em->flush();
    }

    /**
     * Resolve course and session ids from resource links.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveCourseAndSession(ResourceNode $resourceNode): array
    {
        $courseId  = null;
        $sessionId = null;

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            if ($courseId === null && $link->getCourse()) {
                $courseId = $link->getCourse()->getId();
            }

            if ($sessionId === null && $link->getSession()) {
                $sessionId = $link->getSession()->getId();
            }

            if ($courseId !== null && $sessionId !== null) {
                break;
            }
        }

        return [$courseId, $sessionId];
    }
}
