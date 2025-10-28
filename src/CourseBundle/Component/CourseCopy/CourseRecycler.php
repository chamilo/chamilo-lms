<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course as CoreCourse;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CWiki;
use Doctrine\ORM\EntityManagerInterface;

final class CourseRecycler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $courseCode,
        private readonly int $courseId
    ) {}

    /**
     * $type: 'full_backup' | 'select_items' ; $selected: [type => [id => true]].
     */
    public function recycle(string $type, array $selected): void
    {
        $isFull = ('full_backup' === $type);

        // If your EM doesn't have wrapInTransaction(), replace by $this->em->transactional(fn() => { ... })
        $this->em->wrapInTransaction(function () use ($isFull, $selected): void {
            $this->unplugCertificateDocsForCourse();

            // Links & categories
            $this->recycleGeneric($isFull, CLink::class, $selected['link'] ?? []);
            $this->recycleGeneric($isFull, CLinkCategory::class, $selected['link_category'] ?? [], autoClean: true);

            // Calendar & announcements
            $this->recycleGeneric($isFull, CCalendarEvent::class, $selected['event'] ?? []);
            $this->recycleGeneric($isFull, CAnnouncement::class, $selected['announcement'] ?? []);

            // Documents
            $this->recycleGeneric($isFull, CDocument::class, $selected['document'] ?? [], deleteFiles: true);

            // Forums & forum categories
            $this->recycleGeneric($isFull, CForum::class, $selected['forum'] ?? [], cascadeHeavy: true);
            $this->recycleGeneric($isFull, CForumCategory::class, $selected['forum_category'] ?? [], autoClean: true);

            // Quizzes & categories
            $this->recycleGeneric($isFull, CQuiz::class, $selected['quiz'] ?? [], cascadeHeavy: true);
            $this->recycleGeneric($isFull, CQuizCategory::class, $selected['test_category'] ?? []);

            // Surveys
            $this->recycleGeneric($isFull, CSurvey::class, $selected['survey'] ?? [], cascadeHeavy: true);

            // Learning paths & categories
            $this->recycleGeneric($isFull, CLp::class, $selected['learnpath'] ?? [], cascadeHeavy: true, scormCleanup: true);
            $this->recycleLpCategories($isFull, $selected['learnpath_category'] ?? []);

            // Other resources
            $this->recycleGeneric($isFull, CCourseDescription::class, $selected['course_description'] ?? []);
            $this->recycleGeneric($isFull, CWiki::class, $selected['wiki'] ?? [], cascadeHeavy: true);
            $this->recycleGeneric($isFull, CGlossary::class, $selected['glossary'] ?? []);
            $this->recycleGeneric($isFull, CThematic::class, $selected['thematic'] ?? [], cascadeHeavy: true);
            $this->recycleGeneric($isFull, CAttendance::class, $selected['attendance'] ?? [], cascadeHeavy: true);
            $this->recycleGeneric($isFull, CStudentPublication::class, $selected['work'] ?? [], cascadeHeavy: true);

            if ($isFull) {
                // If you keep cleaning course picture:
                // CourseManager::deleteCoursePicture($this->courseCode);
            }
        });
    }

    /**
     * Generic recycler for any AbstractResource-based entity.
     * - If $isFull => deletes *all resources of that type* for the course.
     * - If partial => deletes only the provided $ids.
     * Options:
     *  - deleteFiles: physical files are already handled by hardDelete (if repo supports it).
     *  - cascadeHeavy: for heavy-relations types (forums, LPs). hardDelete should traverse.
     *  - autoClean: e.g. remove empty categories after deleting links/forums.
     *  - scormCleanup: if LP SCORM → hook storage service if needed.
     */
    private function recycleGeneric(
        bool $isFull,
        string $entityClass,
        array $idsMap,
        bool $deleteFiles = false,
        bool $cascadeHeavy = false,
        bool $autoClean = false,
        bool $scormCleanup = false
    ): void {
        $repo = $this->em->getRepository($entityClass);
        $hasHardDelete = method_exists($repo, 'hardDelete');

        if ($isFull) {
            $resources = $this->fetchResourcesForCourse($entityClass, null);
            if ($resources) {
                $this->hardDeleteMany($entityClass, $resources);

                // Physical delete fallback for documents if repo lacks hardDelete()
                if ($deleteFiles && !$hasHardDelete && CDocument::class === $entityClass) {
                    foreach ($resources as $res) {
                        $this->physicallyDeleteDocumentFiles($res);
                    }
                }
            }

            if ($autoClean) {
                $this->autoCleanIfSupported($entityClass);
            }
            if ($scormCleanup && CLp::class === $entityClass) {
                $this->cleanupScormDirsForAllLp();
            }

            return;
        }

        $ids = $this->ids($idsMap);
        if (!$ids) {
            if ($autoClean) {
                $this->autoCleanIfSupported($entityClass);
            }

            return;
        }

        $resources = $this->fetchResourcesForCourse($entityClass, $ids);
        if ($resources) {
            $this->hardDeleteMany($entityClass, $resources);

            if ($deleteFiles && !$hasHardDelete && CDocument::class === $entityClass) {
                foreach ($resources as $res) {
                    $this->physicallyDeleteDocumentFiles($res);
                }
            }
        }

        if ($autoClean) {
            $this->autoCleanIfSupported($entityClass);
        }
        if ($scormCleanup && CLp::class === $entityClass) {
            $this->cleanupScormDirsForLpIds($ids);
        }
    }

    /**
     * LP categories: detach LPs and then delete selected/all categories.
     */
    private function recycleLpCategories(bool $isFull, array $idsMap): void
    {
        if ($isFull) {
            // Detach all categories from LPs in course
            $this->clearLpCategoriesForCourse();
            $this->deleteAllOfTypeForCourse(CLpCategory::class);

            return;
        }

        $ids = $this->ids($idsMap);
        if (!$ids) {
            return;
        }

        // Detach LPs from these categories
        $this->clearLpCategoriesForIds($ids);
        $this->deleteSelectedOfTypeForCourse(CLpCategory::class, $ids);
    }

    /**
     * Normalizes IDs from [id => true] maps into int/string scalars.
     */
    private function ids(array $map): array
    {
        return array_values(array_filter(array_map(
            static fn ($k) => is_numeric($k) ? (int) $k : (string) $k,
            array_keys($map)
        ), static fn ($v) => '' !== $v && null !== $v));
    }

    /**
     * Lightweight Course reference for query builders.
     */
    private function courseRef(): CoreCourse
    {
        /** @var CoreCourse $ref */
        return $this->em->getReference(CoreCourse::class, $this->courseId);
    }

    /**
     * Fetches resources by entity class within course, optionally filtering by resource iid.
     * If the repository doesn't extend ResourceRepository, falls back to a generic QB.
     *
     * @return array<int, AbstractResource>
     */
    private function fetchResourcesForCourse(string $entityClass, ?array $ids = null): array
    {
        $repo = $this->em->getRepository($entityClass);

        // Path A: repository exposes ResourceRepository API
        if (method_exists($repo, 'getResourcesByCourseIgnoreVisibility')) {
            $qb = $repo->getResourcesByCourseIgnoreVisibility($this->courseRef());
            if ($ids && \count($ids) > 0) {
                // Try iid first; if the entity has no iid, fall back to id
                $meta = $this->em->getClassMetadata($entityClass);
                $hasIid = $meta->hasField('iid');

                if ($hasIid) {
                    $qb->andWhere('resource.iid IN (:ids)');
                } else {
                    $qb->andWhere('resource.id IN (:ids)');
                }
                $qb->setParameter('ids', $ids);
            }

            return $qb->getQuery()->getResult();
        }

        // Path B: generic fallback (join to ResourceNode/ResourceLinks and filter by course)
        $qb = $this->em->createQueryBuilder()
            ->select('resource')
            ->from($entityClass, 'resource')
            ->innerJoin('resource.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('links.course = :course')
            ->setParameter('course', $this->courseRef())
        ;

        if ($ids && \count($ids) > 0) {
            $meta = $this->em->getClassMetadata($entityClass);
            $field = $meta->hasField('iid') ? 'resource.iid' : 'resource.id';
            $qb->andWhere("$field IN (:ids)")->setParameter('ids', $ids);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Force-unlink associations that can trigger cascade-persist on delete.
     * We always null GradebookCategory->document before removing the category.
     */
    private function preUnlinkBeforeDelete(array $entities): void
    {
        $changed = false;

        foreach ($entities as $e) {
            if ($e instanceof GradebookCategory
                && method_exists($e, 'getDocument')
                && method_exists($e, 'setDocument')
            ) {
                if (null !== $e->getDocument()) {
                    // Prevent "new entity found through relationship" on flush
                    $e->setDocument(null);
                    $this->em->persist($e);
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $this->em->flush();
        }
    }

    /**
     * Hard-deletes a list of resources. If repository doesn't provide hardDelete(),
     * falls back to EM->remove() and a final flush (expect proper cascade mappings).
     */
    private function hardDeleteMany(string $entityClass, array $resources): void
    {
        $repo = $this->em->getRepository($entityClass);

        // Unlink problematic associations up front (prevents cascade-persist on flush)
        $this->preUnlinkBeforeDelete($resources);

        $usedFallback = false;
        foreach ($resources as $res) {
            if (method_exists($repo, 'hardDelete')) {
                // Repo handles full hard delete (nodes/links/files)
                $repo->hardDelete($res);
            } else {
                // Fallback: standard remove (expect proper cascades elsewhere)
                $this->em->remove($res);
                $usedFallback = true;
            }
        }

        // Always flush once at the end of the batch to materialize changes
        $this->em->flush();

        // Optional: clear EM to reduce memory in huge batches
        // $this->em->clear();
    }

    /**
     * Deletes all resources of a type in the course.
     */
    private function deleteAllOfTypeForCourse(string $entityClass): void
    {
        $resources = $this->fetchResourcesForCourse($entityClass, null);
        if ($resources) {
            $this->hardDeleteMany($entityClass, $resources);
        }
    }

    /**
     * Deletes selected resources (by iid) of a type in the course.
     */
    private function deleteSelectedOfTypeForCourse(string $entityClass, array $ids): void
    {
        if (!$ids) {
            return;
        }
        $resources = $this->fetchResourcesForCourse($entityClass, $ids);
        if ($resources) {
            $this->hardDeleteMany($entityClass, $resources);
        }
    }

    /**
     * Optional post-clean for empty categories if repository supports it.
     */
    private function autoCleanIfSupported(string $entityClass): void
    {
        $repo = $this->em->getRepository($entityClass);
        if (method_exists($repo, 'deleteEmptyByCourse')) {
            $repo->deleteEmptyByCourse($this->courseId);
        }
    }

    /**
     * Detach categories from ALL LPs in course (repo-level bulk method preferred if available).
     */
    private function clearLpCategoriesForCourse(): void
    {
        $lps = $this->fetchResourcesForCourse(CLp::class, null);
        $changed = false;
        foreach ($lps as $lp) {
            if (method_exists($lp, 'getCategory') && method_exists($lp, 'setCategory')) {
                if ($lp->getCategory()) {
                    $lp->setCategory(null);
                    $this->em->persist($lp);
                    $changed = true;
                }
            }
        }
        if ($changed) {
            $this->em->flush();
        }
    }

    /**
     * Detach categories only for LPs that are linked to given category ids.
     */
    private function clearLpCategoriesForIds(array $catIds): void
    {
        $lps = $this->fetchResourcesForCourse(CLp::class, null);
        $changed = false;
        foreach ($lps as $lp) {
            $cat = method_exists($lp, 'getCategory') ? $lp->getCategory() : null;
            $catId = $cat?->getId();
            if (null !== $catId && \in_array($catId, $catIds, true) && method_exists($lp, 'setCategory')) {
                $lp->setCategory(null);
                $this->em->persist($lp);
                $changed = true;
            }
        }
        if ($changed) {
            $this->em->flush();
        }
    }

    private function unplugCertificateDocsForCourse(): void
    {
        // Detach any certificate-type document from gradebook categories of this course
        // Reason: avoid "A new entity was found through the relationship ... #document" on flush.
        $qb = $this->em->createQueryBuilder()
            ->select('c', 'd')
            ->from(GradebookCategory::class, 'c')
            ->innerJoin('c.course', 'course')
            ->leftJoin('c.document', 'd')
            ->where('course.id = :cid')
            ->andWhere('d IS NOT NULL')
            ->andWhere('d.filetype = :ft')
            ->setParameter('cid', $this->courseId)
            ->setParameter('ft', 'certificate')
        ;

        /** @var GradebookCategory[] $cats */
        $cats = $qb->getQuery()->getResult();

        $changed = false;
        foreach ($cats as $cat) {
            $doc = $cat->getDocument();
            if ($doc instanceof CDocument) {
                // Prevent transient Document from being cascaded/persisted during delete
                $cat->setDocument(null);
                $this->em->persist($cat);
                $changed = true;
            }
        }

        if ($changed) {
            // Materialize unlink before any deletion happens
            $this->em->flush();
        }
    }

    /** @param CDocument $doc */
    private function physicallyDeleteDocumentFiles(AbstractResource $doc): void
    {
        // This generic example traverses node->resourceFiles and removes them from disk.
        $node = $doc->getResourceNode();
        if (!method_exists($node, 'getResourceFiles')) {
            return;
        }

        foreach ($node->getResourceFiles() as $rf) {
            // Example: if you have an absolute path getter or storage key
            if (method_exists($rf, 'getAbsolutePath')) {
                $path = (string) $rf->getAbsolutePath();
                if ($path && file_exists($path)) {
                    @unlink($path);
                }
            }
            // If you use a storage service, call it here instead of unlink()
            // $this->storage->delete($rf->getStorageKey());
        }
    }

    /**
     * SCORM directory cleanup for ALL LPs (hook your storage service here if needed).
     */
    private function cleanupScormDirsForAllLp(): void
    {
        // If you have a storage/scorm service, invoke it here.
        // By default, nothing: hardDelete already deletes files linked to ResourceNode.
    }

    /**
     * SCORM directory cleanup for selected LPs.
     */
    private function cleanupScormDirsForLpIds(array $lpIds): void
    {
        // Same as above, but limited to provided LP ids.
    }
}
