<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CNotebook.
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="c_notebook"
 * )
 */
class CNotebook extends AbstractResource implements ResourceInterface
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
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected int $userId;

    /**
     * @ORM\Column(name="course", type="string", length=40, nullable=false)
     */
    protected string $course;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected string $title;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected string $description;

    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    protected DateTime $creationDate;

    /**
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(name="update_date", type="datetime", nullable=false)
     */
    protected DateTime $updateDate;

    /**
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    protected ?int $status;

    public function __construct()
    {
        $this->status = 0;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * Set userId.
     *
     * @return CNotebook
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set course.
     *
     * @return CNotebook
     */
    public function setCourse(string $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course.
     *
     * @return string
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set sessionId.
     *
     * @return CNotebook
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

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set creationDate.
     *
     * @return CNotebook
     */
    public function setCreationDate(DateTime $creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set updateDate.
     *
     * @return CNotebook
     */
    public function setUpdateDate(DateTime $updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * Set status.
     *
     * @return CNotebook
     */
    public function setStatus(int $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set cId.
     *
     * @return CNotebook
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
     * Get iid.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
