<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215160445 extends AbstractMigrationChamilo
{
    /** Batch size for post processing. Larger than BATCH_SIZE because posts have no file I/O. */
    private const POST_BATCH_SIZE = 200;

    public function getDescription(): string
    {
        return 'Migrate c_forum tables';
    }

    public function up(Schema $schema): void
    {
        $forumCategoryRepo = $this->container->get(CForumCategoryRepository::class);
        $forumRepo         = $this->container->get(CForumRepository::class);
        $forumThreadRepo   = $this->container->get(CForumThreadRepository::class);
        $forumPostRepo     = $this->container->get(CForumPostRepository::class);
        $courseRepo        = $this->container->get(CourseRepository::class);

        /** @var Kernel $kernel */
        $kernel   = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course   = $courseRepo->find($courseId);
            $admin    = $this->getAdmin();

            // ----------------------------------------------------------------
            // 1. Forum categories (small set – keep simple, batch flush)
            // ----------------------------------------------------------------
            $sql    = "SELECT * FROM c_forum_category WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items  = $result->fetchAllAssociative();

            foreach ($items as $itemData) {
                $id = $itemData['iid'];

                /** @var CForumCategory $resource */
                $resource = $forumCategoryRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $result = $this->fixItemProperty(
                    'forum_category',
                    $forumCategoryRepo,
                    $course,
                    $admin,
                    $resource,
                    $course
                );

                if (false === $result) {
                    continue;
                }

                $this->entityManager->persist($resource);
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            // ----------------------------------------------------------------
            // 2. Forums (small set – keep simple, batch flush)
            // ----------------------------------------------------------------
            $sql    = "SELECT * FROM c_forum_forum WHERE c_id = {$courseId} ORDER BY iid";
            $result = $this->connection->executeQuery($sql);
            $items  = $result->fetchAllAssociative();

            $course = $courseRepo->find($courseId);
            $admin  = $this->getAdmin();

            foreach ($items as $itemData) {
                $id = $itemData['iid'];

                /** @var CForum $resource */
                $resource = $forumRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $parent     = null;
                $categoryId = $itemData['forum_category'];
                if (!empty($categoryId)) {
                    $parent = $forumCategoryRepo->find($categoryId);
                }

                if (null === $parent) {
                    $parent = $course;
                }

                $result = $this->fixItemProperty(
                    'forum',
                    $forumRepo,
                    $course,
                    $admin,
                    $resource,
                    $parent
                );

                $this->entityManager->persist($resource);

                $forumImage = $itemData['forum_image'];
                if (!empty($forumImage)) {
                    $filePath = $this->getUpdateRootPath().'/app/courses/'.$course->getDirectory().'/upload/forum/images/'.$forumImage;
                    error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                    if ($this->fileExists($filePath)) {
                        $this->addLegacyFileToResource($filePath, $forumRepo, $resource, $id, $forumImage);
                    }
                }

                if (false === $result) {
                    continue;
                }

                $this->entityManager->persist($resource);
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            // ----------------------------------------------------------------
            // 3. Threads (small set – prefetch item properties, batch flush)
            // ----------------------------------------------------------------
            $sql         = "SELECT * FROM c_forum_thread WHERE c_id = {$courseId} ORDER BY iid";
            $result      = $this->connection->executeQuery($sql);
            $threadItems = $result->fetchAllAssociative();

            $course = $courseRepo->find($courseId);
            $admin  = $this->getAdmin();

            // Prefetch all c_item_property for threads in this course at once.
            $allThreadRefs     = array_map(static fn($r) => (int) $r['iid'], $threadItems);
            $threadPropsMap    = $this->fetchItemPropertiesMap('forum_thread', $courseId, $allThreadRefs);

            // Batch-load all CForumThread entities for this course.
            $threadEntities = !empty($allThreadRefs) ? $forumThreadRepo->findBy(['iid' => $allThreadRefs]) : [];
            $threadEntityMap = [];
            foreach ($threadEntities as $t) {
                $threadEntityMap[$t->getIid()] = $t;
            }

            foreach ($threadItems as $itemData) {
                $id = (int) $itemData['iid'];

                $resource = $threadEntityMap[$id] ?? null;
                if (null === $resource || $resource->hasResourceNode()) {
                    continue;
                }

                $forumId = (int) $itemData['forum_id'];
                if (empty($forumId)) {
                    continue;
                }

                /** @var CForum|null $forum */
                $forum = $forumRepo->find($forumId);
                if (null === $forum) {
                    continue;
                }

                $result = $this->fixItemProperty(
                    'forum_thread',
                    $forumThreadRepo,
                    $course,
                    $admin,
                    $resource,
                    $forum,
                    $threadPropsMap[$id] ?? []
                );

                if (false === $result) {
                    continue;
                }

                $this->entityManager->persist($resource);
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            // ----------------------------------------------------------------
            // 4. Posts (1.3 M rows – full batch optimisation)
            //
            //    Key improvements vs. original:
            //    a) Fetch all post rows for the course once, split into chunks.
            //    b) fetchItemPropertiesMap() prefetches c_item_property for the
            //       entire chunk (1 query / batch instead of 1 / post).
            //    c) findBy(['iid' => $refs]) batch-loads CForumPost entities
            //       (1 query / batch instead of 1 / post).
            //    d) Thread map built once per batch via a single findBy() call;
            //       only 717 threads total so the cost is negligible.
            //    e) Single flush() per batch (not per post).
            // ----------------------------------------------------------------
            $sql       = "SELECT iid, thread_id FROM c_forum_post WHERE c_id = {$courseId} ORDER BY iid";
            $postItems = $this->connection->executeQuery($sql)->fetchAllAssociative();

            $total         = \count($postItems);
            $postBatchSize = self::POST_BATCH_SIZE;

            for ($offset = 0; $offset < $total; $offset += $postBatchSize) {
                // Reload managed entities after previous clear().
                $course = $courseRepo->find($courseId);
                if (null === $course) {
                    break;
                }
                $admin = $this->getAdmin();

                // Rebuild thread map with freshly-managed entities.
                $threadRows = $this->connection->fetchAllAssociative(
                    'SELECT iid FROM c_forum_thread WHERE c_id = :cid',
                    ['cid' => $courseId]
                );
                $threadIds = array_map(static fn($r) => (int) $r['iid'], $threadRows);
                $freshThreads = !empty($threadIds) ? $forumThreadRepo->findBy(['iid' => $threadIds]) : [];
                $threadMap = [];
                foreach ($freshThreads as $t) {
                    $threadMap[$t->getIid()] = $t;
                }

                $chunk = \array_slice($postItems, $offset, $postBatchSize);
                $refs  = array_map(static fn($r) => (int) $r['iid'], $chunk);

                // Prefetch c_item_property for all posts in this chunk.
                $itemPropsMap = $this->fetchItemPropertiesMap('forum_post', $courseId, $refs);

                // Batch-load CForumPost entities for this chunk.
                $postEntities = $forumPostRepo->findBy(['iid' => $refs]);
                $postMap      = [];
                foreach ($postEntities as $p) {
                    $postMap[$p->getIid()] = $p;
                }

                foreach ($chunk as $itemData) {
                    $id = (int) $itemData['iid'];

                    /** @var CForumPost|null $resource */
                    $resource = $postMap[$id] ?? null;
                    if (null === $resource || $resource->hasResourceNode()) {
                        continue;
                    }

                    if (empty(trim($resource->getTitle()))) {
                        $resource->setTitle(\sprintf('Post #%s', $id));
                    }

                    $threadId = (int) $itemData['thread_id'];
                    if (empty($threadId)) {
                        continue;
                    }

                    $thread = $threadMap[$threadId] ?? null;
                    if (null === $thread) {
                        continue;
                    }

                    $forum = $thread->getForum();
                    if (null === $forum) {
                        continue;
                    }

                    $result = $this->fixItemProperty(
                        'forum_post',
                        $forumPostRepo,
                        $course,
                        $admin,
                        $resource,
                        $thread,
                        $itemPropsMap[$id] ?? []
                    );

                    if (false === $result) {
                        continue;
                    }

                    $this->entityManager->persist($resource);
                }

                // Single flush per batch – not per post.
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            // ----------------------------------------------------------------
            // 5. Post attachments (file I/O – keep one flush per file)
            // ----------------------------------------------------------------
            $course = $courseRepo->find($courseId);
            if (null === $course) {
                continue;
            }

            $sql   = "SELECT * FROM c_forum_attachment WHERE c_id = {$courseId} ORDER BY iid";
            $items = $this->connection->executeQuery($sql)->fetchAllAssociative();

            foreach ($items as $itemData) {
                $id       = $itemData['iid'];
                $postId   = (int) $itemData['post_id'];
                $path     = $itemData['path'];
                $fileName = $itemData['filename'];

                /** @var CForumPost|null $post */
                $post = $forumPostRepo->find($postId);

                if (null === $post || !$post->hasResourceNode()) {
                    continue;
                }

                if (!empty($fileName) && !empty($path)) {
                    $filePath = $this->getUpdateRootPath().'/app/courses/'.$course->getDirectory().'/upload/forum/'.$path;
                    error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                    if ($this->fileExists($filePath)) {
                        $this->addLegacyFileToResource($filePath, $forumPostRepo, $post, $id, $fileName);
                        $this->entityManager->persist($post);
                        $this->entityManager->flush();
                    }
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }
    }
}
