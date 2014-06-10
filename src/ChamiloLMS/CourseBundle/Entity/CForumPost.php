<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumPost
 *
 * @ORM\Table(name="c_forum_post", indexes={@ORM\Index(name="poster_id", columns={"poster_id"}), @ORM\Index(name="forum_id", columns={"forum_id"}), @ORM\Index(name="idx_forum_post_thread_id", columns={"thread_id"}), @ORM\Index(name="idx_forum_post_visible", columns={"visible"})})
 * @ORM\Entity
 */
class CForumPost
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
     * @ORM\Column(name="post_id", type="integer", nullable=false)
     */
    private $postId;

    /**
     * @var string
     *
     * @ORM\Column(name="post_title", type="string", length=250, nullable=true)
     */
    private $postTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="post_text", type="text", nullable=true)
     */
    private $postText;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_id", type="integer", nullable=true)
     */
    private $threadId;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_id", type="integer", nullable=true)
     */
    private $forumId;

    /**
     * @var integer
     *
     * @ORM\Column(name="poster_id", type="integer", nullable=true)
     */
    private $posterId;

    /**
     * @var string
     *
     * @ORM\Column(name="poster_name", type="string", length=100, nullable=true)
     */
    private $posterName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="post_date", type="datetime", nullable=true)
     */
    private $postDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="post_notification", type="boolean", nullable=true)
     */
    private $postNotification;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_parent_id", type="integer", nullable=true)
     */
    private $postParentId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", nullable=true)
     */
    private $visible;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
