<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumForum
 *
 * @ORM\Table(name="c_forum_forum")
 * @ORM\Entity
 */
class CForumForum
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
     * @ORM\Column(name="forum_id", type="integer", nullable=false)
     */
    private $forumId;

    /**
     * @var string
     *
     * @ORM\Column(name="forum_title", type="string", length=255, nullable=false)
     */
    private $forumTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="forum_comment", type="text", nullable=true)
     */
    private $forumComment;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_threads", type="integer", nullable=true)
     */
    private $forumThreads;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_posts", type="integer", nullable=true)
     */
    private $forumPosts;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_last_post", type="integer", nullable=true)
     */
    private $forumLastPost;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_category", type="integer", nullable=true)
     */
    private $forumCategory;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_anonymous", type="integer", nullable=true)
     */
    private $allowAnonymous;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_edit", type="integer", nullable=true)
     */
    private $allowEdit;

    /**
     * @var string
     *
     * @ORM\Column(name="approval_direct_post", type="string", length=20, nullable=true)
     */
    private $approvalDirectPost;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_attachments", type="integer", nullable=true)
     */
    private $allowAttachments;

    /**
     * @var integer
     *
     * @ORM\Column(name="allow_new_threads", type="integer", nullable=true)
     */
    private $allowNewThreads;

    /**
     * @var string
     *
     * @ORM\Column(name="default_view", type="string", length=20, nullable=true)
     */
    private $defaultView;

    /**
     * @var string
     *
     * @ORM\Column(name="forum_of_group", type="string", length=20, nullable=true)
     */
    private $forumOfGroup;

    /**
     * @var string
     *
     * @ORM\Column(name="forum_group_public_private", type="string", length=20, nullable=true)
     */
    private $forumGroupPublicPrivate;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_order", type="integer", nullable=true)
     */
    private $forumOrder;

    /**
     * @var integer
     *
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    private $locked;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="forum_image", type="string", length=255, nullable=false)
     */
    private $forumImage;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime", nullable=false)
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime", nullable=false)
     */
    private $endTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
