<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SessionTree
 *
 * @ORM\Table(name="session_tree")
 * @ORM\Entity(repositoryClass="Entity\Repository\SessionTreeRepository")
 * @Gedmo\Tree(type="nested")
 */
class SessionTree
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
     * @ORM\Column(name="session_path_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionPathId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="course_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $courseId;

    /**
     * @var integer
     *
     * @ORM\Column(name="tool_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $toolId;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $type;

    /**
     * @var integer
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $lft;

    /**
     * @var integer
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $lvl;

    /**
     * @var integer
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $rgt;

    /**
     * @var integer
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $root;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $parentId;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="SessionTree", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="SessionTree", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="Tool")
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="id")
     */
    private $tool;

    /**
     * @ORM\ManyToOne(targetEntity="SessionPath")
     * @ORM\JoinColumn(name="session_path_id", referencedColumnName="id")
     */
    private $sessionPath;

    /**
     * @ORM\ManyToOne(targetEntity="Course")
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id")
     */
    private $course;

    /**
     * @ORM\ManyToOne(targetEntity="Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session;

    public function getTool()
    {
        return $this->tool;
    }

    public function setTool($tool)
    {
        $this->tool = $tool;
        return $this;
    }

    public function getCourse()
    {
        return $this->course;
    }

    public function setCourse($course)
    {
        $this->course = $course;
        return $this;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function setSession($session)
    {
        $this->session = $session;
        return $this;
    }

    public function getSessionPath()
    {
        return $this->sessionPath;
    }

    public function setSessionPath($sessionPath)
    {
        $this->sessionPath = $sessionPath;
        return $this;
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
     * Set sessionPathId
     *
     * @param integer $sessionPathId
     * @return SessionTree
     */
    public function setSessionPathId($sessionPathId)
    {
        $this->sessionPathId = $sessionPathId;

        return $this;
    }

    /**
     * Get sessionPathId
     *
     * @return integer
     */
    public function getSessionPathId()
    {
        return $this->sessionPathId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return SessionTree
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set courseId
     *
     * @param integer $courseId
     * @return SessionTree
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * Get courseId
     *
     * @return integer
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * Set toolId
     *
     * @param integer $toolId
     * @return SessionTree
     */
    public function setToolId($toolId)
    {
        $this->toolId = $toolId;

        return $this;
    }

    /**
     * Get toolId
     *
     * @return integer
     */
    public function getToolId()
    {
        return $this->toolId;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return SessionTree
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return SessionTree
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     * @return SessionTree
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl
     *
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return SessionTree
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set root
     *
     * @param integer $root
     * @return SessionTree
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root
     *
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     * @return SessionTree
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }
}
