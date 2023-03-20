<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="c_forum_thread_qualify",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="user_id", columns={"user_id", "thread_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CForumThreadQualify
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CForumThread", inversedBy="qualifications")
     * @ORM\JoinColumn(name="thread_id", referencedColumnName="iid", nullable=true, onDelete="CASCADE")
     */
    protected CForumThread $thread;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="qualify_user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $qualifyUser;

    /**
     * @ORM\Column(name="qualify", type="float", precision=6, scale=2, nullable=false)
     */
    protected float $qualify;

    /**
     * @ORM\Column(name="qualify_time", type="datetime", nullable=true)
     */
    protected ?DateTime $qualifyTime = null;

    public function setQualify(float $qualify): self
    {
        $this->qualify = $qualify;

        return $this;
    }

    /**
     * Get qualify.
     *
     * @return float
     */
    public function getQualify()
    {
        return $this->qualify;
    }

    public function setQualifyTime(DateTime $qualifyTime): self
    {
        $this->qualifyTime = $qualifyTime;

        return $this;
    }

    /**
     * Get qualifyTime.
     *
     * @return DateTime
     */
    public function getQualifyTime()
    {
        return $this->qualifyTime;
    }

    /**
     * Set cId.
     *
     * @return CForumThreadQualify
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

    public function getThread(): CForumThread
    {
        return $this->thread;
    }

    public function setThread(CForumThread $thread): self
    {
        $this->thread = $thread;

        return $this;
    }
}
