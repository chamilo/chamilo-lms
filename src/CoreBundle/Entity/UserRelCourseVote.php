<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserRelCourseVote.
 *
 * @ApiResource(
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      iri="http://schema.org/userRelCourseVote",
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      normalizationContext={"groups"={"user:read"}},
 *      denormalizationContext={"groups"={"user:write"}},
 *      collectionOperations={"get"},
 *      itemOperations={
 *          "get"={},
 *          "put"={},
 *          "delete"={},
 *     }
 * )
 * @ORM\Table(name="user_rel_course_vote", indexes={
 *     @ORM\Index(name="idx_ucv_cid", columns={"c_id"}),
 *     @ORM\Index(name="idx_ucv_uid", columns={"user_id"}),
 *     @ORM\Index(name="idx_ucv_cuid", columns={"user_id", "c_id"})
 * })
 * @ORM\Entity
 */
class UserRelCourseVote
{
    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected $cId;

    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="userRelCourseVotes"
     * )
     * @ORM\JoinColumn(
     *    name="user_id",
     *    referencedColumnName="id",
     *    onDelete="CASCADE"
     * )
     */
    protected $user;

    /**
     * Get user.
     *
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="url_id", type="integer", nullable=false)
     */
    protected $urlId;

    /**
     * @var int
     *
     * @ORM\Column(name="vote", type="integer", nullable=false)
     */
    protected $vote;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return UserRelCourseVote
     */
    public function setCId($cId)
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
     * @param int $sessionId
     *
     * @return UserRelCourseVote
     */
    public function setSessionId($sessionId)
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
     * @param int $urlId
     *
     * @return UserRelCourseVote
     */
    public function setUrlId($urlId)
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
     * @param int $vote
     *
     * @return UserRelCourseVote
     */
    public function setVote($vote)
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
