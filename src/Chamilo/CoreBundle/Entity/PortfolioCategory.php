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
 * @package Chamilo\CoreBundle\Entity
 *
 * @ORM\Table(
 *  name="portfolio_category",
 *  indexes={
 *      @ORM\Index(name="user", columns={"user_id"})
 *  }
 * )
 * Add @ to the next line if api_get_configuration_value('allow_portfolio_tool') is true
 * ORM\Entity()
 */
class PortfolioCategory
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected string $title;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="is_visible", type="boolean", options={"default": true})
     */
    protected bool $isVisible = true;

    /**
     * @ORM\Column(name="parent_id", type="integer", nullable=false, options={"default": 0})
     */
    protected int $parentId = 0;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Portfolio", mappedBy="category")
     */
    protected Collection $items;

    /**
     * PortfolioCategory constructor.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): PortfolioCategory
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): PortfolioCategory
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): PortfolioCategory
    {
        $this->description = $description;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): PortfolioCategory
    {
        $this->user = $user;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): PortfolioCategory
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId): PortfolioCategory
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getItems(Course $course = null, Session $session = null, bool $onlyVisibles = false): Collection
    {
        $criteria = Criteria::create();

        if ($onlyVisibles) {
            $criteria->andWhere(
                Criteria::expr()->eq('visibility', Portfolio::VISIBILITY_VISIBLE)
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

        $criteria->orderBy(['creationDate' => 'DESC']);

        return $this->items->matching($criteria);
    }

    public function setItems(Collection $items): PortfolioCategory
    {
        $this->items = $items;

        return $this;
    }
}
