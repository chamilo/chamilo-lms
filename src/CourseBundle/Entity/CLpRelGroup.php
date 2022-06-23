<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(
 *     name="c_lp_rel_group"
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Repository\CLpRelGroupRepository")
 */
class CLpRelGroup
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLp")
     * @ORM\JoinColumn(name="lp_id", referencedColumnName="iid")
     */
    protected CLp $lp;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    protected Course $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     */
    protected ?Session $session = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroup")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="iid", nullable=false)
     */
    protected CGroup $group;

    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected DateTime $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
     */
    protected $creatorUser;

    /**
     * @return int
     */
    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * @param int $iid
     *
     * @return CLpRelGroup
     */
    public function setIid(int $iid): self
    {
        $this->iid = $iid;

        return $this;
    }

    /**
     * @return CLp
     */
    public function getLp(): CLp
    {
        return $this->lp;
    }

    /**
     * @param CLp $lp
     *
     * @return CLpRelGroup
     */
    public function setLp(CLp $lp): self
    {
        $this->lp = $lp;

        return $this;
    }

    /**
     * @return Course
     */
    public function getCourse(): Course
    {
        return $this->course;
    }

    /**
     * @param Course $course
     *
     * @return CLpRelGroup
     */
    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return Session|null
     */
    public function getSession(): ?Session
    {
        return $this->session;
    }

    /**
     * @param Session|null $session
     *
     * @return CLpRelGroup
     */
    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return CGroup
     */
    public function getGroup(): CGroup
    {
        return $this->group;
    }

    /**
     * @param CGroup $group
     *
     * @return CLpRelGroup
     */
    public function setGroup(CGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return CLpRelGroup
     */
    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatorUser()
    {
        return $this->creatorUser;
    }

    /**
     * @param mixed $creatorUser
     *
     * @return CLpRelGroup
     */
    public function setCreatorUser($creatorUser): self
    {
        $this->creatorUser = $creatorUser;

        return $this;
    }

}
