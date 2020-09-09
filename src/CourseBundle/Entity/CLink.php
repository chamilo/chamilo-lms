<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CLink.
 *
 * @ORM\Table(name="c_link")
 * @ORM\Entity
 */
class CLink extends AbstractResource implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name="url", type="text", nullable=false)
     */
    protected $url;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(name="title", type="string", length=150, nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var CLinkCategory|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLinkCategory", inversedBy="links")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="iid")
     */
    protected $category;

    /**
     * @var int
     *
     * @ORM\Column(name="display_order", type="integer", nullable=false)
     */
    protected $displayOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="on_homepage", type="string", length=10, nullable=false)
     */
    protected $onHomepage;

    /**
     * @var string
     *
     * @ORM\Column(name="target", type="string", length=10, nullable=true)
     */
    protected $target;

    public function __construct()
    {
        $this->displayOrder = 0;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return CLink
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CLink
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return (string) $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CLink
     */
    public function setDescription($description)
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
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return CLink
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set onHomepage.
     *
     * @param string $onHomepage
     *
     * @return CLink
     */
    public function setOnHomepage($onHomepage)
    {
        $this->onHomepage = $onHomepage;

        return $this;
    }

    /**
     * Get onHomepage.
     *
     * @return string
     */
    public function getOnHomepage()
    {
        return $this->onHomepage;
    }

    /**
     * Set target.
     *
     * @param string $target
     *
     * @return CLink
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target.
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function getCategory(): ?CLinkCategory
    {
        return $this->category;
    }

    public function setCategory(?CLinkCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName($name): self
    {
        return $this->setTitle($name);
    }
}
