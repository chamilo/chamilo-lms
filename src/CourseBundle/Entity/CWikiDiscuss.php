<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CWikiDiscuss
 *
 * @ORM\Table(
 *  name="c_wiki_discuss",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CWikiDiscuss
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="publication_id", type="integer", nullable=false)
     */
    private $publicationId;

    /**
     * @var integer
     *
     * @ORM\Column(name="userc_id", type="integer", nullable=false)
     */
    private $usercId;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=false)
     */
    private $comment;

    /**
     * @var string
     *
     * @ORM\Column(name="p_score", type="string", length=255, nullable=true)
     */
    private $pScore;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dtime", type="datetime", nullable=false)
     */
    private $dtime;

    /**
     * Set publicationId
     *
     * @param integer $publicationId
     * @return CWikiDiscuss
     */
    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;

        return $this;
    }

    /**
     * Get publicationId
     *
     * @return integer
     */
    public function getPublicationId()
    {
        return $this->publicationId;
    }

    /**
     * Set usercId
     *
     * @param integer $usercId
     * @return CWikiDiscuss
     */
    public function setUsercId($usercId)
    {
        $this->usercId = $usercId;

        return $this;
    }

    /**
     * Get usercId
     *
     * @return integer
     */
    public function getUsercId()
    {
        return $this->usercId;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return CWikiDiscuss
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set pScore
     *
     * @param string $pScore
     * @return CWikiDiscuss
     */
    public function setPScore($pScore)
    {
        $this->pScore = $pScore;

        return $this;
    }

    /**
     * Get pScore
     *
     * @return string
     */
    public function getPScore()
    {
        return $this->pScore;
    }

    /**
     * Set dtime
     *
     * @param \DateTime $dtime
     * @return CWikiDiscuss
     */
    public function setDtime($dtime)
    {
        $this->dtime = $dtime;

        return $this;
    }

    /**
     * Get dtime
     *
     * @return \DateTime
     */
    public function getDtime()
    {
        return $this->dtime;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CWikiDiscuss
     */
    public function setId($id)
    {
        $this->id = $id;

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
     * Set cId
     *
     * @param integer $cId
     * @return CWikiDiscuss
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
}
