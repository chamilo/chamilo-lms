<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Portfolio.
 *
 * @ORM\Table(
 *     name="portfolio",
 *     indexes={
 *         @ORM\Index(name="user", columns={"user_id"}),
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="session", columns={"session_id"}),
 *         @ORM\Index(name="category", columns={"category_id"})
 *     }
 * )
 * @ORM\Entity()
 */
class Portfolio
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="content", type="text")
     */
    protected string $content;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected Course $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    protected Session $session;

    /**
     * @ORM\Column(name="creation_date", type="datetime")
     */
    protected DateTime $creationDate;

    /**
     * @ORM\Column(name="update_date", type="datetime")
     */
    protected DateTime $updateDate;

    /**
     * @ORM\Column(name="is_visible", type="boolean", options={"default":true})
     */
    protected bool $isVisible = true;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\PortfolioCategory", inversedBy="items")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    protected PortfolioCategory $category;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\PortfolioComment", mappedBy="item")
     */
    private $comments;

    /**
     * @var int|null
     *
     * @ORM\Column(name="origin", type="integer", nullable=true)
     */
    private $origin;
    /**
     * @var int|null
     *
     * @ORM\Column(name="origin_type", type="integer", nullable=true)
     */
    private $originType;

    /**
     * @var float|null
     *
     * @ORM\Column(name="score", type="float", nullable=true)
     */
    private $score;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_highlighted", type="boolean", options={"default": false})
     */
    private $isHighlighted = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_template", type="boolean", options={"default": false})
     */
    private $isTemplate = false;

    public function __construct()
    {
        $this->category = new PortfolioCategory();
    }

    /**
     * Set course.
     *
     * @return Portfolio
     */
    public function setCourse(Course $course = null)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course.
     *
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Get session.
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set session.
     *
     * @return Portfolio
     */
    public function setSession(Session $session = null)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Set title.
     *
     * @return Portfolio
     */
    public function setTitle(string $title)
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
        return $this->title;
    }

    /**
     * Set content.
     *
     * @return Portfolio
     */
    public function setContent(string $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set creationDate.
     *
     * @return Portfolio
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
     * @return Portfolio
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set isVisible.
     *
     * @return Portfolio
     */
    public function setIsVisible(bool $isVisible)
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    /**
     * Get category.
     *
     * @return PortfolioCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set category.
     *
     * @return Portfolio
     */
    public function setCategory(PortfolioCategory $category = null)
    {
        $this->category = $category;

        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getLastComments(int $number = 3, bool $avoidPerUserVisibility = false): Collection
    {
        $criteria = Criteria::create();
        $criteria
            ->orderBy(['date' => 'DESC'])
            ->setMaxResults($number);

        if ($avoidPerUserVisibility) {
            $criteria->where(
                Criteria::expr()->neq('visibility', PortfolioComment::VISIBILITY_PER_USER)
            );
        }

        return $this->comments->matching($criteria);
    }

    public function getOrigin(): ?int
    {
        return $this->origin;
    }

    /**
     * @return \Chamilo\CoreBundle\Entity\Portfolio
     */
    public function setOrigin(?int $origin): Portfolio
    {
        $this->origin = $origin;

        return $this;
    }

    public function getOriginType(): ?int
    {
        return $this->originType;
    }

    /**
     * @return \Chamilo\CoreBundle\Entity\Portfolio
     */
    public function setOriginType(?int $originType): Portfolio
    {
        $this->originType = $originType;

        return $this;
    }

    public function getExcerpt(int $count = 380): string
    {
        return api_get_short_text_from_html($this->content, $count);
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): void
    {
        $this->score = $score;
    }

    public function isHighlighted(): bool
    {
        return $this->isHighlighted;
    }

    public function setIsHighlighted(bool $isHighlighted): Portfolio
    {
        $this->isHighlighted = $isHighlighted;

        return $this;
    }

    public function isTemplate(): bool
    {
        return $this->isTemplate;
    }

    public function setIsTemplate(bool $isTemplate): Portfolio
    {
        $this->isTemplate = $isTemplate;

        return $this;
    }
}
