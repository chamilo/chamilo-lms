<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CurriculumItem
 *
 * @ORM\Table(name="curriculum_item")
 * @ORM\Entity
 */
class CurriculumItem
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $categoryId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="score", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $score;

    /**
     * @var boolean
     *
     * @ORM\Column(name="max_repeat", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $maxRepeat;

    /**
     * @ORM\ManyToOne(targetEntity="CurriculumCategory", inversedBy="items")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=true)
     */
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="CurriculumItemRelUser", mappedBy="item")
     * @ORM\OrderBy({"orderId" = "ASC"})
     */
    private $userItems;

    /**
     *
     */
    public function __construct()
    {
        $this->userItems = new ArrayCollection();
    }

    /**
     *
     * @return CurriculumCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     *
     * @return ArrayCollection
     */
    public function getUserItems()
    {
        return $this->userItems;
    }

    /**
     * @param $userItem
     */
    public function setUserItems($userItem)
    {
        $this->userItems = $userItem;
    }


    /**
     * @param CurriculumCategory $category
     */
    public function setCategory(CurriculumCategory $category)
    {
        $this->category = $category;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     * @return CurriculumItem
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return CurriculumItem
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set score
     *
     * @param integer $score
     * @return CurriculumItem
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return integer
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set maxRepeat
     *
     * @param boolean $maxRepeat
     * @return CurriculumItem
     */
    public function setMaxRepeat($maxRepeat)
    {
        $this->maxRepeat = $maxRepeat;

        return $this;
    }

    /**
     * Get maxRepeat
     *
     * @return boolean
     */
    public function getMaxRepeat()
    {
        return $this->maxRepeat;
    }
}
