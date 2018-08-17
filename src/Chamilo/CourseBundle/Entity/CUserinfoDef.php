<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CUserinfoDef.
 *
 * @ORM\Table(
 *  name="c_userinfo_def",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CUserinfoDef
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
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=80, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var bool
     *
     * @ORM\Column(name="line_count", type="boolean", nullable=false)
     */
    protected $lineCount;

    /**
     * @var bool
     *
     * @ORM\Column(name="rank", type="boolean", nullable=false)
     */
    protected $rank;

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return CUserinfoDef
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
        return $this->title;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     *
     * @return CUserinfoDef
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set lineCount.
     *
     * @param bool $lineCount
     *
     * @return CUserinfoDef
     */
    public function setLineCount($lineCount)
    {
        $this->lineCount = $lineCount;

        return $this;
    }

    /**
     * Get lineCount.
     *
     * @return bool
     */
    public function getLineCount()
    {
        return $this->lineCount;
    }

    /**
     * Set rank.
     *
     * @param bool $rank
     *
     * @return CUserinfoDef
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank.
     *
     * @return bool
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CUserinfoDef
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set cId.
     *
     * @param int $cId
     *
     * @return CUserinfoDef
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
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
}
