<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelCourseVote
 *
 * @ORM\Table(name="user_rel_course_vote", indexes={@ORM\Index(name="idx_ucv_cid", columns={"c_id"}), @ORM\Index(name="idx_ucv_uid", columns={"user_id"}), @ORM\Index(name="idx_ucv_cuid", columns={"user_id", "c_id"})})
 * @ORM\Entity
 */
class UserRelCourseVote
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
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="url_id", type="integer", nullable=false)
     */
    private $urlId;

    /**
     * @var integer
     *
     * @ORM\Column(name="vote", type="integer", nullable=false)
     */
    private $vote;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
