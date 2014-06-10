<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CGroupCategory
 *
 * @ORM\Table(name="c_group_category")
 * @ORM\Entity
 */
class CGroupCategory
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="doc_state", type="boolean", nullable=false)
     */
    private $docState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="calendar_state", type="boolean", nullable=false)
     */
    private $calendarState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="work_state", type="boolean", nullable=false)
     */
    private $workState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="announcements_state", type="boolean", nullable=false)
     */
    private $announcementsState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="forum_state", type="boolean", nullable=false)
     */
    private $forumState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="wiki_state", type="boolean", nullable=false)
     */
    private $wikiState;

    /**
     * @var boolean
     *
     * @ORM\Column(name="chat_state", type="boolean", nullable=false)
     */
    private $chatState;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_student", type="integer", nullable=false)
     */
    private $maxStudent;

    /**
     * @var boolean
     *
     * @ORM\Column(name="self_reg_allowed", type="boolean", nullable=false)
     */
    private $selfRegAllowed;

    /**
     * @var boolean
     *
     * @ORM\Column(name="self_unreg_allowed", type="boolean", nullable=false)
     */
    private $selfUnregAllowed;

    /**
     * @var integer
     *
     * @ORM\Column(name="groups_per_user", type="integer", nullable=false)
     */
    private $groupsPerUser;

    /**
     * @var integer
     *
     * @ORM\Column(name="display_order", type="integer", nullable=false)
     */
    private $displayOrder;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
