<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CBlog.
 *
 * @ORM\Table(
 *     name="c_blog",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="session_id", columns={"session_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CBlog extends AbstractResource implements ResourceInterface
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
     * @ORM\Column(name="blog_id", type="integer")
     */
    protected int $blogId;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="blog_name", type="string", length=250, nullable=false)
     */
    protected string $blogName;

    /**
     * @ORM\Column(name="blog_subtitle", type="string", length=250, nullable=true)
     */
    protected ?string $blogSubtitle = null;

    /**
     * @ORM\Column(name="date_creation", type="datetime", nullable=false)
     */
    protected DateTime $dateCreation;

    /**
     * @ORM\Column(name="visibility", type="boolean", nullable=false)
     */
    protected bool $visibility;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected ?int $sessionId = null;

    public function __toString(): string
    {
        return $this->getBlogName();
    }

    /**
     * Set blogName.
     *
     * @return CBlog
     */
    public function setBlogName(string $blogName)
    {
        $this->blogName = $blogName;

        return $this;
    }

    /**
     * Get blogName.
     *
     * @return string
     */
    public function getBlogName()
    {
        return $this->blogName;
    }

    /**
     * Set blogSubtitle.
     *
     * @return CBlog
     */
    public function setBlogSubtitle(string $blogSubtitle)
    {
        $this->blogSubtitle = $blogSubtitle;

        return $this;
    }

    /**
     * Get blogSubtitle.
     *
     * @return string
     */
    public function getBlogSubtitle()
    {
        return $this->blogSubtitle;
    }

    /**
     * Set dateCreation.
     *
     * @return CBlog
     */
    public function setDateCreation(DateTime $dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     *
     * @return DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set visibility.
     *
     * @return CBlog
     */
    public function setVisibility(bool $visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility.
     *
     * @return bool
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set sessionId.
     *
     * @return CBlog
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
     * Set blogId.
     *
     * @return CBlog
     */
    public function setBlogId(int $blogId)
    {
        $this->blogId = $blogId;

        return $this;
    }

    /**
     * Get blogId.
     *
     * @return int
     */
    public function getBlogId()
    {
        return $this->blogId;
    }

    /**
     * Set cId.
     *
     * @return CBlog
     */
    public function setCId(int $cId)
    {
        $this->cId = $cId;

        return $this;
    }

    public function getIid(): int
    {
        return $this->iid;
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

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getBlogName();
    }

    public function setResourceName(string $name): self
    {
        return $this->setBlogName($name);
    }
}
