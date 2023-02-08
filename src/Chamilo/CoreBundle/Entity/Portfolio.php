<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Portfolio.
 *
 * @package Chamilo\CoreBundle\Entity
 *
 * @ORM\Table(
 *  name="portfolio",
 *  indexes={
 *   @ORM\Index(name="user", columns={"user_id"}),
 *   @ORM\Index(name="course", columns={"c_id"}),
 *   @ORM\Index(name="session", columns={"session_id"}),
 *   @ORM\Index(name="category", columns={"category_id"})
 *  }
 * )
 * Add @ to the next line if api_get_configuration_value('allow_portfolio_tool') is true
 * ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\PortfolioRepository")
 */
class Portfolio
{
    public const TYPE_ITEM = 1;
    public const TYPE_COMMENT = 2;

    public const VISIBILITY_HIDDEN = 0;
    public const VISIBILITY_VISIBLE = 1;
    public const VISIBILITY_HIDDEN_EXCEPT_TEACHER = 2;
    public const VISIBILITY_PER_USER = 3;

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
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="content", type="text")
     */
    protected $content;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $course = null;

    /**
     * @var Session
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $session = null;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime")
     */
    protected $creationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetime")
     */
    protected $updateDate;

    /**
     * @var int
     *
     * @ORM\Column(name="visibility", type="smallint", options={"default": 1})
     */
    protected $visibility = 1;

    /**
     * @var \Chamilo\CoreBundle\Entity\PortfolioCategory
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\PortfolioCategory", inversedBy="items")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $category;

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

    /**
     * Portfolio constructor.
     */
    public function __construct()
    {
        $this->category = null;
        $this->comments = new ArrayCollection();
    }

    /**
     * Set user.
     *
     * @return Portfolio
     */
    public function setUser(User $user)
    {
        $this->user = $user;

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
     * @param string $title
     *
     * @return Portfolio
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     */
    public function getTitle(bool $stripTags = false): string
    {
        if ($stripTags) {
            return strip_tags($this->title);
        }

        return $this->title;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return Portfolio
     */
    public function setContent($content)
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
    public function setCreationDate(\DateTime $creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return \DateTime
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
    public function setUpdateDate(\DateTime $updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime
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
     */
    public function setVisibility(int $visibility): Portfolio
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get isVisible.
     */
    public function getVisibility(): int
    {
        return $this->visibility;
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
