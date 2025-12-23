<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\SearchEngineRef;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

/**
 * Handles Xapian indexing for course descriptions.
 */
final class CourseDescriptionXapianIndexer
{
    public function __construct(
        private readonly XapianIndexService $xapianIndexService,
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settingsManager,
    ) {}

    /**
     * Index or reindex a course description.
     *
     * @return int|null Xapian document id or null when indexing is skipped
     */
    public function indexCourseDescription(CCourseDescription $description): ?int
    {
        // Global feature toggle
        $enabled = (string) $this->settingsManager->getSetting('search.search_enabled', true);
        if ('true' !== $enabled) {
            return null;
        }

        // Per-request flag set from the form
        if ($description->shouldSkipSearchIndex()) {
            return null;
        }

        $resourceNode = $description->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return null;
        }

        // Resolve course & session
        [$courseId, $sessionId] = $this->resolveCourseAndSession($resourceNode);

        $title = (string) ($description->getTitle() ?? '');
        $body = (string) ($description->getContent() ?? '');
        $content = trim($title.' '.$body);

        $fields = [
            'kind' => 'course_description',
            'tool' => 'course_description',
            'title' => $title,
            'description' => $title,
            'content' => $content,
            'resource_node_id' => (string) $resourceNode->getId(),
            'course_id' => null !== $courseId ? (string) $courseId : '',
            'session_id' => null !== $sessionId ? (string) $sessionId : '',
            'xapian_data' => json_encode([
                'type' => 'course_description',
                'description_id' => (int) $description->getIid(),
                'course_id' => $courseId,
                'session_id' => $sessionId,
            ]),
        ];

        $terms = ['Tcourse_description'];
        if (null !== $courseId) {
            $terms[] = 'C'.$courseId;
        }
        if (null !== $sessionId) {
            $terms[] = 'S'.$sessionId;
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
                // Best-effort delete
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

    public function deleteCourseDescriptionIndex(CCourseDescription $description): void
    {
        $resourceNode = $description->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
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
