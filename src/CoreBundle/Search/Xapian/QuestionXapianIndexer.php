<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\SearchEngineRef;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

/**
 * Handles Xapian indexing for quiz questions.
 *
 * Only the question itself (title/description) is indexed, not answers.
 */
final class QuestionXapianIndexer
{
    public function __construct(
        private readonly XapianIndexService $xapianIndexService,
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * Index or reindex a quiz question.
     *
     * @return int|null Xapian document id or null when indexing is skipped
     */
    public function indexQuestion(CQuizQuestion $question): ?int
    {
        $resourceNode = $question->getResourceNode();

        // Global feature toggle
        $enabled = (string) $this->settingsManager->getSetting('search.search_enabled', true);
        if ('true' !== $enabled) {
            return null;
        }

        if (!$resourceNode instanceof ResourceNode) {
            // Question without a resource node cannot be indexed
            return null;
        }

        // Quiz context for the question (first quiz + all quizzes)
        [$primaryQuizId, $allQuizIds] = $this->getQuizContext($question);

        // Resolve course and session from the question resource node
        [$courseId, $sessionId] = $this->resolveCourseAndSession($resourceNode);

        $title = (string) $question->getQuestion();
        $description = (string) ($question->getDescription() ?? '');

        // Index only the question itself (title + description), not the answers
        $content = trim($title.' '.$description);

        $fields = [
            'kind' => 'question',
            'tool' => 'quiz_question',
            'title' => $title,
            'description' => $description,
            'content' => $content,
            'resource_node_id' => (string) $resourceNode->getId(),
            'question_id' => (string) $question->getIid(),
            'quiz_id' => null !== $primaryQuizId ? (string) $primaryQuizId : '',
            'course_id' => null !== $courseId ? (string) $courseId : '',
            'session_id' => null !== $sessionId ? (string) $sessionId : '',
            'xapian_data' => json_encode([
                'type' => 'exercise_question',
                'question_id' => (int) $question->getIid(),
                'quiz_ids' => $allQuizIds,
                'primary_quiz_id' => $primaryQuizId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
            ]),
        ];

        // Terms for filtering
        $terms = ['Tquiz_question', 'Tquestion'];
        if (null !== $courseId) {
            $terms[] = 'C'.$courseId;
        }
        if (null !== $sessionId) {
            $terms[] = 'S'.$sessionId;
        }
        if (null !== $primaryQuizId) {
            $terms[] = 'Q'.$primaryQuizId;
        }

        $resourceNodeRef = $this->em->getReference(ResourceNode::class, (int) $resourceNode->getId());

        /** @var SearchEngineRef|null $existingRef */
        $existingRef = $this->em
            ->getRepository(SearchEngineRef::class)
            ->findOneBy(['resourceNode' => $resourceNodeRef])
        ;

        $existingDocId = $existingRef?->getSearchDid();

        if (null !== $existingDocId) {
            try {
                $this->xapianIndexService->deleteDocument($existingDocId);
            } catch (Throwable) {
                // Best-effort delete: ignore errors here
            }
        }

        try {
            $docId = $this->xapianIndexService->indexDocument($fields, $terms);
        } catch (Throwable) {
            return null;
        }

        if ($existingRef instanceof SearchEngineRef) {
            $existingRef->setSearchDid($docId);
        } else {
            $existingRef = new SearchEngineRef();
            $existingRef->setResourceNode($resourceNodeRef);
            $existingRef->setSearchDid($docId);
            $this->em->persist($existingRef);
        }

        $this->em->flush();

        return $docId;
    }

    /**
     * Delete question index (called on entity removal).
     */
    public function deleteQuestionIndex(CQuizQuestion $question): void
    {
        $resourceNode = $question->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return;
        }

        $enabled = (string) $this->settingsManager->getSetting('search.search_enabled', true);
        if ('true' !== $enabled) {
            return;
        }

        $resourceNodeRef = $this->em->getReference(ResourceNode::class, (int) $resourceNode->getId());

        /** @var SearchEngineRef|null $ref */
        $ref = $this->em
            ->getRepository(SearchEngineRef::class)
            ->findOneBy(['resourceNode' => $resourceNodeRef])
        ;

        if (!$ref) {
            return;
        }

        try {
            $this->xapianIndexService->deleteDocument($ref->getSearchDid());
        } catch (Throwable) {
            // Best-effort delete
        }

        $this->em->remove($ref);
        $this->em->flush();
    }

    /**
     * Resolve course and session ids from resource links.
     *
     * @return array{0:int|null,1:int|null}
     */
    private function resolveCourseAndSession(ResourceNode $resourceNode): array
    {
        $courseId = null;
        $sessionId = null;

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            if (null === $courseId && $link->getCourse()) {
                $courseId = $link->getCourse()->getId();
            }

            if (null === $sessionId && $link->getSession()) {
                $sessionId = $link->getSession()->getId();
            }

            if (null !== $courseId && null !== $sessionId) {
                break;
            }
        }

        return [$courseId, $sessionId];
    }

    /**
     * Returns the "primary" quiz id for the question and the list of all quiz ids.
     *
     * @return array{0:int|null,1:array<int,int>}
     */
    private function getQuizContext(CQuizQuestion $question): array
    {
        $primaryQuizId = null;
        $quizIds = [];

        foreach ($question->getRelQuizzes() as $rel) {
            if (!$rel instanceof CQuizRelQuestion) {
                continue;
            }

            $quiz = $rel->getQuiz();
            if (!$quiz instanceof CQuiz) {
                continue;
            }

            $quizId = $quiz->getIid();
            if (null === $quizId) {
                continue;
            }

            if (null === $primaryQuizId) {
                $primaryQuizId = $quizId;
            }

            if (!\in_array($quizId, $quizIds, true)) {
                $quizIds[] = $quizId;
            }
        }

        return [$primaryQuizId, $quizIds];
    }
}
