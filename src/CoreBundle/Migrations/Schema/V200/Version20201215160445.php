<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Repository\CForumAttachmentRepository;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CForumThreadRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;

final class Version20201215160445 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_forum tables';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $forumCategoryRepo = $container->get(CForumCategoryRepository::class);
        $forumRepo = $container->get(CForumRepository::class);
        $forumAttachmentRepo = $container->get(CForumAttachmentRepository::class);
        $forumThreadRepo = $container->get(CForumThreadRepository::class);
        $forumPostRepo = $container->get(CForumPostRepository::class);

        $courseRepo = $container->get(CourseRepository::class);
        $sessionRepo = $container->get(SessionRepository::class);
        $groupRepo = $container->get(CGroupRepository::class);
        $userRepo = $container->get(UserRepository::class);

        /** @var Kernel $kernel */
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $q = $em->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = $course->getId();
            $course = $courseRepo->find($courseId);

            $admin = $this->getAdmin();

            // Categories.
            $sql = "SELECT * FROM c_forum_category WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
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

                $em->persist($resource);
                $em->flush();
            }

            $em->flush();
            $em->clear();

            // Forums.
            $sql = "SELECT * FROM c_forum_forum WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();

            $admin = $this->getAdmin();
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                /** @var CForum $resource */
                $resource = $forumRepo->find($id);
                if ($resource->hasResourceNode()) {
                    continue;
                }

                $course = $courseRepo->find($courseId);

                $parent = null;
                $categoryId = $itemData['forum_category'];
                if (!empty($categoryId)) {
                    $parent = $forumCategoryRepo->find($categoryId);
                }

                // Parent should not be null, because every forum must have a category, in this case use the course
                // as parent.
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

                $em->persist($resource);
                $em->flush();

                $forumImage = $itemData['forum_image'];
                if (!empty($forumImage)) {
                    $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/upload/forum/images/'.$forumImage;
                    if ($this->fileExists($filePath)) {
                        $this->addLegacyFileToResource($filePath, $forumRepo, $resource, $id, $forumImage);
                    }
                }

                if (false === $result) {
                    continue;
                }
                $em->persist($resource);
                $em->flush();
            }
            $em->flush();
            $em->clear();

            // Threads.
            $sql = "SELECT * FROM c_forum_thread WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            $admin = $this->getAdmin();

            foreach ($items as $itemData) {
                $id = (int) $itemData['iid'];
                /** @var CForumThread $resource */
                $resource = $forumThreadRepo->find($id);
                if ($resource->hasResourceNode()) {
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

                $course = $courseRepo->find($courseId);

                $result = $this->fixItemProperty(
                    'forum_thread',
                    $forumThreadRepo,
                    $course,
                    $admin,
                    $resource,
                    $forum
                );

                if (false === $result) {
                    continue;
                }

                $em->persist($resource);
                $em->flush();
            }

            $em->flush();
            $em->clear();

            // Posts.
            $sql = "SELECT * FROM c_forum_post WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
            $admin = $this->getAdmin();
            foreach ($items as $itemData) {
                $id = (int) $itemData['iid'];
                /** @var CForumPost $resource */
                $resource = $forumPostRepo->find($id);

                if ($resource->hasResourceNode()) {
                    continue;
                }

                if (empty(trim($resource->getPostTitle()))) {
                    $resource->setPostTitle(sprintf('Post #%s', $resource->getIid()));
                }

                $threadId = (int) $itemData['thread_id'];

                if (empty($threadId)) {
                    continue;
                }

                /** @var CForumThread|null $thread */
                $thread = $forumThreadRepo->find($threadId);

                if (null === $thread) {
                    continue;
                }

                $forum = $thread->getForum();

                // For some reason the thread doesn't have a forum, so we ignore the thread posts.
                if (null === $forum) {
                    continue;
                }

                $course = $courseRepo->find($courseId);

                $result = $this->fixItemProperty(
                    'forum_post',
                    $forumPostRepo,
                    $course,
                    $admin,
                    $resource,
                    $thread
                );

                if (false === $result) {
                    continue;
                }

                $em->persist($resource);
                $em->flush();
            }

            $em->flush();
            $em->clear();

            // Post attachments
            $sql = "SELECT * FROM c_forum_attachment WHERE c_id = {$courseId}
                    ORDER BY iid";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();

            $forumPostRepo = $container->get(CForumPostRepository::class);
            foreach ($items as $itemData) {
                $id = $itemData['iid'];
                $postId = (int) $itemData['post_id'];
                $path = $itemData['path'];
                $fileName = $itemData['filename'];

                /** @var CForumPost|null $post */
                $post = $forumPostRepo->find($postId);

                if (null === $post || !$post->hasResourceNode()) {
                    continue;
                }

                if (!empty($fileName) && !empty($path)) {
                    $filePath = $rootPath.'/app/courses/'.$course->getDirectory().'/upload/forum/'.$path;
                    if ($this->fileExists($filePath)) {
                        $this->addLegacyFileToResource($filePath, $forumPostRepo, $post, $id, $fileName);
                        $em->persist($post);
                        $em->flush();
                    }
                }
            }
            $em->flush();
            $em->clear();
        }
    }
}
