<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumThread
 *
 * @ORM\Table(name="c_forum_thread", indexes={@ORM\Index(name="idx_forum_thread_forum_id", columns={"forum_id"})})
 * @ORM\Entity
 */
class CForumThread
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
     * @ORM\Column(name="thread_id", type="integer", nullable=false)
     */
    private $threadId;

    /**
     * @var string
     *
     * @ORM\Column(name="thread_title", type="string", length=255, nullable=true)
     */
    private $threadTitle;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_id", type="integer", nullable=true)
     */
    private $forumId;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_replies", type="integer", nullable=true)
     */
    private $threadReplies;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_poster_id", type="integer", nullable=true)
     */
    private $threadPosterId;

    /**
     * @var string
     *
     * @ORM\Column(name="thread_poster_name", type="string", length=100, nullable=true)
     */
    private $threadPosterName;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_views", type="integer", nullable=true)
     */
    private $threadViews;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_last_post", type="integer", nullable=true)
     */
    private $threadLastPost;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="thread_date", type="datetime", nullable=true)
     */
    private $threadDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="thread_sticky", type="boolean", nullable=true)
     */
    private $threadSticky;

    /**
     * @var integer
     *
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    private $locked;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    private $sessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="thread_title_qualify", type="string", length=255, nullable=true)
     */
    private $threadTitleQualify;

    /**
     * @var float
     *
     * @ORM\Column(name="thread_qualify_max", type="float", precision=6, scale=2, nullable=false)
     */
    private $threadQualifyMax;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="thread_close_date", type="datetime", nullable=true)
     */
    private $threadCloseDate;

    /**
     * @var float
     *
     * @ORM\Column(name="thread_weight", type="float", precision=6, scale=2, nullable=false)
     */
    private $threadWeight;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
