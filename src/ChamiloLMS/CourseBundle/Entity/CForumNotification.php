<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumNotification
 *
 * @ORM\Table(name="c_forum_notification", indexes={@ORM\Index(name="user_id", columns={"user_id"}), @ORM\Index(name="forum_id", columns={"forum_id"})})
 * @ORM\Entity
 */
class CForumNotification
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_id", type="integer", nullable=false)
     */
    private $forumId;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_id", type="integer", nullable=false)
     */
    private $threadId;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="integer", nullable=false)
     */
    private $postId;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
