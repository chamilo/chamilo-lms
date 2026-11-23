<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use Doctrine\DBAL\Schema\Schema;

/**
 * V200\Version20201215160445 skipped every 1.11 forum post without a c_item_property row (the
 * first post of some threads, among others): they kept no resource node, so the voter refused
 * any reply to them. It now gives such a post the contexts of its thread; this repairs the
 * platforms that were migrated before.
 */
final class Version20261124100000 extends AbstractMigrationChamilo
{
    private const int POST_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Give the forum posts left without a resource node the course, session and group of their thread';
    }

    public function up(Schema $schema): void
    {
        $postIds = array_map('intval', $this->connection->fetchFirstColumn(
            'SELECT iid FROM c_forum_post WHERE resource_node_id IS NULL ORDER BY iid'
        ));
        if ([] === $postIds) {
            return;
        }

        /** @var CForumPostRepository $postRepository */
        $postRepository = $this->container->get(CForumPostRepository::class);
        $repaired = 0;
        $skipped = [];

        foreach (array_chunk($postIds, self::POST_BATCH_SIZE) as $batch) {
            foreach ($batch as $postId) {
                $post = $this->entityManager->find(CForumPost::class, $postId);
                $thread = $post?->getThread();
                if (!$post instanceof CForumPost || !$thread instanceof CForumThread || !$thread->hasResourceNode()) {
                    $skipped[] = $postId;

                    continue;
                }

                $threadLinks = array_filter(
                    $thread->getResourceNode()->getResourceLinks()->toArray(),
                    static fn (ResourceLink $link): bool => !$link->isDeleted() && null !== $link->getCourse(),
                );
                if ([] === $threadLinks) {
                    $skipped[] = $postId;

                    continue;
                }

                $post->setParent($thread);
                $postRepository->addResourceNode($post, $post->getUser(), $thread, null, false);

                foreach ($threadLinks as $threadLink) {
                    $visible = (bool) $post->getVisible()
                        && ResourceLink::VISIBILITY_PUBLISHED === $threadLink->getVisibility();

                    $post->addCourseLink(
                        $threadLink->getCourse(),
                        $threadLink->getSession(),
                        $threadLink->getGroup(),
                        $visible ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT,
                    );
                }

                $this->entityManager->persist($post);
                ++$repaired;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $this->write(\sprintf('Forum posts given a resource node: %d.', $repaired));
        if ([] !== $skipped) {
            // Their thread has no resource node either: it must be repaired first.
            $this->write(\sprintf(
                'Forum posts left without a resource node because their thread has none: %s.',
                implode(', ', $skipped)
            ));
        }
    }

    public function down(Schema $schema): void {}
}
