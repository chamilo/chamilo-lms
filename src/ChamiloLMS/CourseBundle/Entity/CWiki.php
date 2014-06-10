<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CWiki
 *
 * @ORM\Table(name="c_wiki", indexes={@ORM\Index(name="reflink", columns={"reflink"}), @ORM\Index(name="group_id", columns={"group_id"}), @ORM\Index(name="page_id", columns={"page_id"}), @ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity
 */
class CWiki
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
     * @ORM\Column(name="page_id", type="integer", nullable=false)
     */
    private $pageId;

    /**
     * @var string
     *
     * @ORM\Column(name="reflink", type="string", length=255, nullable=false)
     */
    private $reflink;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="integer", nullable=true)
     */
    private $groupId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dtime", type="datetime", nullable=false)
     */
    private $dtime;

    /**
     * @var integer
     *
     * @ORM\Column(name="addlock", type="integer", nullable=false)
     */
    private $addlock;

    /**
     * @var integer
     *
     * @ORM\Column(name="editlock", type="integer", nullable=false)
     */
    private $editlock;

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility", type="integer", nullable=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @ORM\Column(name="addlock_disc", type="integer", nullable=false)
     */
    private $addlockDisc;

    /**
     * @var integer
     *
     * @ORM\Column(name="visibility_disc", type="integer", nullable=false)
     */
    private $visibilityDisc;

    /**
     * @var integer
     *
     * @ORM\Column(name="ratinglock_disc", type="integer", nullable=false)
     */
    private $ratinglockDisc;

    /**
     * @var integer
     *
     * @ORM\Column(name="assignment", type="integer", nullable=false)
     */
    private $assignment;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=false)
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="progress", type="text", nullable=false)
     */
    private $progress;

    /**
     * @var integer
     *
     * @ORM\Column(name="score", type="integer", nullable=true)
     */
    private $score;

    /**
     * @var integer
     *
     * @ORM\Column(name="version", type="integer", nullable=true)
     */
    private $version;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_editing", type="integer", nullable=false)
     */
    private $isEditing;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_edit", type="datetime", nullable=false)
     */
    private $timeEdit;

    /**
     * @var integer
     *
     * @ORM\Column(name="hits", type="integer", nullable=true)
     */
    private $hits;

    /**
     * @var string
     *
     * @ORM\Column(name="linksto", type="text", nullable=false)
     */
    private $linksto;

    /**
     * @var string
     *
     * @ORM\Column(name="tag", type="text", nullable=false)
     */
    private $tag;

    /**
     * @var string
     *
     * @ORM\Column(name="user_ip", type="string", length=39, nullable=false)
     */
    private $userIp;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
