<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelCourseVote.
 *
 * @ORM\Table(name="user_rel_course_vote", indexes={
 *     @ORM\Index(name="idx_ucv_cid", columns={"c_id"}),
 *     @ORM\Index(name="idx_ucv_uid", columns={"user_id"}),
 *     @ORM\Index(name="idx_ucv_cuid", columns={"user_id", "c_id"})
 * })
 * @ORM\Entity
 */
class UserRelCourseVote
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected int $cId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="userRelCourseVotes")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="url_id", type="integer", nullable=false)
     */
    protected int $urlId;

    /**
     * @ORM\Column(name="vote", type="integer", nullable=false)
     */
    protected int $vote;

    /**
     * Set cId.
     *
     * @return UserRelCourseVote
     */
    public function setCId(int $cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set sessionId.
     *
     * @return UserRelCourseVote
     */
    public function setSessionId(int $sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set urlId.
     *
     * @return UserRelCourseVote
     */
    public function setUrlId(int $urlId)
    {
        $this->urlId = $urlId;

        return $this;
    }

    /**
     * Get urlId.
     *
     * @return int
     */
    public function getUrlId()
    {
        return $this->urlId;
    }

    /**
     * Set vote.
     *
     * @return UserRelCourseVote
     */
    public function setVote(int $vote)
    {
        $this->vote = $vote;

        return $this;
    }

    /**
     * Get vote.
     *
     * @return int
     */
    public function getVote()
    {
        return $this->vote;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
