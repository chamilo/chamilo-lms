<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CQuizCategory
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="c_quiz_category")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class CQuizCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $description;

    /**
     *
     * @ORM\Column(name="parent_id", type="bigint", nullable=true)
     */
    private $parentId;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="CQuizCategory", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="iid", onDelete="SET NULL")
     */
    private $parent;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    private $root;

    /**
     * @ORM\OneToMany(targetEntity="CQuizCategory", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     *
     * @ORM\Column(name="visibility", type="integer")
     */
    private $visibility;

    /**
     * @ORM\OneToMany(targetEntity="CQuizQuestionRelCategory", mappedBy="category")
     **/
    private $quizQuestionRelCategoryList;

    public function __construct()
    {
        $this->quizQuestionRelCategoryList = new ArrayCollection();
    }

    public function setParent(CQuizCategory $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getQuestions()
    {
        $questions = new ArrayCollection();
        foreach ($this->quizQuestionRelCategoryList as $relQuestion) {
            $questions[] = $relQuestion->getQuestion();
        }
        return $questions;
    }


    /**
     * Set cId
     *
     * @param integer $cId
     * @return CQuizQuestionCategory
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CQuizQuestionCategory
     */
    public function setIid($id)
    {
        $this->iid = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return CQuizQuestionCategory
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
     * Set description
     *
     * @param string $description
     * @return CQuizQuestionCategory
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
    * Set cId
    *
    * @param integer $cId
    * @return CQuizQuestionCategory
    */
    public function setParentId($id)
    {
       $this->parentId = $id;

       return $this;
    }

    /**
    * Get cId
    *
    * @return integer
    */
    public function getParentId()
    {
       return $this->parentId;
    }

    /**
     * @return integer
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }
}
