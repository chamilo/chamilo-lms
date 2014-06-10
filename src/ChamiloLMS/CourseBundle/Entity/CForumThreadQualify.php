<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumThreadQualify
 *
 * @ORM\Table(name="c_forum_thread_qualify", indexes={@ORM\Index(name="user_id", columns={"user_id", "thread_id"})})
 * @ORM\Entity
 */
class CForumThreadQualify
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
     * @ORM\Column(name="thread_id", type="integer", nullable=false)
     */
    private $threadId;

    /**
     * @var float
     *
     * @ORM\Column(name="qualify", type="float", precision=6, scale=2, nullable=false)
     */
    private $qualify;

    /**
     * @var integer
     *
     * @ORM\Column(name="qualify_user_id", type="integer", nullable=true)
     */
    private $qualifyUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="qualify_time", type="datetime", nullable=true)
     */
    private $qualifyTime;

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
