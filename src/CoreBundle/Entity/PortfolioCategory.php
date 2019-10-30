<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PortfolioCategory.
 *
 * @ORM\Table(
 *  name="portfolio_category",
 *  indexes={
 *      @ORM\Index(name="user", columns={"user_id"})
 *  }
 * )
 * @ORM\Entity
 */
class PortfolioCategory
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    protected $title;

    /**
     * @var null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_visible", type="boolean", options={"default": true})
     */
    protected $isVisible = true;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Portfolio", mappedBy="category")
     */
    protected $items;

    /**
     * PortfolioCategory constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->title;
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

    /**
     * @param int $id
     *
     * @return PortfolioCategory
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return PortfolioCategory
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return PortfolioCategory
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user.
     *
     * @param User $user
     *
     * @return PortfolioCategory
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get isVisible.
     *
     * @return bool
     */
    public function isVisible()
    {
        return $this->isVisible;
    }

    /**
     * Set isVisible.
     *
     * @param bool $isVisible
     *
     * @return PortfolioCategory
     */
    public function setIsVisible($isVisible)
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    /**
     * Get items.
     *
     * @param Course|null  $course
     * @param Session|null $session
     * @param bool         $onlyVisibles
     *
     * @return ArrayCollection
     */
    public function getItems(Course $course = null, Session $session = null, $onlyVisibles = false)
    {
        $criteria = Criteria::create();

        if ($onlyVisibles) {
            $criteria->andWhere(
                Criteria::expr()->eq('isVisible', true)
            );
        }

        if ($course) {
            $criteria
                ->andWhere(
                    Criteria::expr()->eq('course', $course)
                )
                ->andWhere(
                    Criteria::expr()->eq('session', $session)
                );
        }

        return $this->items->matching($criteria);
    }

    /**
     * Set items.
     *
     * @param Collection $items
     *
     * @return PortfolioCategory
     */
    public function setItems(Collection $items)
    {
        $this->items = $items;

        return $this;
    }
}
