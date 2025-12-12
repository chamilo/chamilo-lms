<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\SearchEngineRef;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLp;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

/**
 * Handles Xapian indexing for learning paths (CLp).
 */
final class LpXapianIndexer
{
    public function __construct(
        private readonly XapianIndexService $xapianIndexService,
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * Index or reindex a learning path.
     *
     * @return int|null Xapian document id or null when indexing is skipped
     */
    public function indexLp(CLp $lp): ?int
    {
        // Global feature toggle (same style as QuestionXapianIndexer)
        $enabled = (string) $this->settingsManager->getSetting('search.search_enabled', true);
        if ('true' !== $enabled) {
            return null;
        }

        $resourceNode = $lp->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            // Learning path without a resource node cannot be indexed
            return null;
        }

        // Resolve course and session from resource links
        [$courseId, $sessionId] = $this->resolveCourseAndSession($resourceNode);

        $title = (string) $lp->getTitle();
        $description = (string) ($lp->getDescription() ?? '');
        $content = trim($title.' '.$description);

        // Keep field names consistent across indexers
        $fields = [
            'kind' => 'learnpath',
            'tool' => 'learnpath',
            'title' => $title,
            'description' => $description,
            'content' => $content,
            'filetype' => 'learnpath',
            'resource_node_id' => (string) $resourceNode->getId(),
            'lp_id' => null !== $lp->getIid() ? (string) $lp->getIid() : '',
            'course_id' => null !== $courseId ? (string) $courseId : '',
            'session_id' => null !== $sessionId ? (string) $sessionId : '',
            'full_path' => (string) $lp->getPath(),
            'xapian_data' => json_encode([
                'type' => 'learnpath',
                'lp_id' => $lp->getIid(),
                'course_id' => $courseId,
                'session_id' => $sessionId,
            ]),
        ];

        // Terms for filtering
        $terms = ['Tlearnpath', 'Tlp'];
        if (null !== $courseId) {
            $terms[] = 'C'.$courseId;
        }
        if (null !== $sessionId) {
            $terms[] = 'S'.$sessionId;
        }
        if (null !== $lp->getIid()) {
            $terms[] = 'L'.$lp->getIid();
        }

        // Reuse SearchEngineRef per resource node (same pattern as questions)
        /** @var SearchEngineRef|null $existingRef */
        $existingRef = $this->em
            ->getRepository(SearchEngineRef::class)
            ->findOneBy(['resourceNodeId' => $resourceNode->getId()])
        ;

        $existingDocId = $existingRef?->getSearchDid();

        if (null !== $existingDocId) {
            try {
                $this->xapianIndexService->deleteDocument($existingDocId);
            } catch (Throwable) {
                // Best-effort delete: ignore errors
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
            $existingRef->setResourceNodeId((int) $resourceNode->getId());
            $existingRef->setSearchDid($docId);
            $this->em->persist($existingRef);
        }

        $this->em->flush();

        return $docId;
    }

    /**
     * Delete learning path index (called on entity removal).
     */
    public function deleteLpIndex(CLp $lp): void
    {
        $resourceNode = $lp->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return;
        }

        /** @var SearchEngineRef|null $ref */
        $ref = $this->em
            ->getRepository(SearchEngineRef::class)
            ->findOneBy(['resourceNodeId' => $resourceNode->getId()])
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
}
