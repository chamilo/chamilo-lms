<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CNotebook.
 *
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Repository\CNotebookRepository")
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
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    #[Assert\NotBlank]
    protected string $title;

    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    #[Assert\NotBlank]
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
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

    public function setCreationDate(DateTime $creationDate): self
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

    public function setUpdateDate(DateTime $updateDate): self
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

    public function setStatus(int $status): self
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
