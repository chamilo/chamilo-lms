<?php
/* For licensing terms, see /license.txt */
namespace Chamilo\CourseBundle\Entity\Repository;

use \Doctrine\ORM\EntityRepository;
use \Chamilo\CourseBundle\Entity\CForumThread;
use \Chamilo\CourseBundle\Entity\CForumPost;

/**
 * CForumPostRespository class
 * Repository to manage forum posts
 * @package Chamilo\CourseBundle\Entity\Repository
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class CForumPostRespository extends EntityRepository
{

    /**
     * Get the post list and their reply posts for a forum thread. Recursively
     * @param CForumThread $thread The thread
     * @param strong $orderDirection Optional. The direction to sort the results
     * @param CForumPost $postParent Optional. The parent post
     * @param int $pageCursor Optional. The current page cursor
     * @param int $limit Optional. The count limit of results. Apply for the post without parent
     * @return array The post list
     */
    public function getPostList(
        CForumThread $thread,
        $orderDirection = 'ASC',
        CForumPost $postParent = null,
        $pageCursor = null,
        $limit = null
    )
    {
        $offset = null;

        if (!empty($pageCursor) && !empty($limit)) {
            $offset = ($pageCursor - 1) * $limit;
        }

        $posts = $this->findBy(
            [
                'threadId' => intval($thread->getThreadId()),
                'cId' => intval($thread->getCId()),
                'visible' => true,
                'postParentId' => is_null($postParent) ? 0 : $postParent->getPostId()
            ],
            ['postId' => $orderDirection],
            $limit,
            $offset
        );

        $list = [];

        foreach ($posts as $post) {
            $list[] = $post;

            $list = array_merge(
                $list,
                $this->getPostList($thread, $orderDirection, $post, null, null)
            );
        }

        return $list;
    }

}
