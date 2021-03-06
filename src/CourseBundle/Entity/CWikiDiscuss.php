<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CWikiDiscuss.
 *
 * @ORM\Table(
 *     name="c_wiki_discuss",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CWikiDiscuss
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="publication_id", type="integer", nullable=false)
     */
    protected int $publicationId;

    /**
     * @ORM\Column(name="userc_id", type="integer", nullable=false)
     */
    protected int $usercId;

    /**
     * @ORM\Column(name="comment", type="text", nullable=false)
     */
    protected string $comment;

    /**
     * @ORM\Column(name="p_score", type="string", length=255, nullable=true)
     */
    protected ?string $pScore = null;

    /**
     * @ORM\Column(name="dtime", type="datetime", nullable=false)
     */
    protected DateTime $dtime;

    /**
     * Get publicationId.
     *
     * @return int
     */
    public function getPublicationId()
    {
        return $this->publicationId;
    }

    /**
     * Set publicationId.
     *
     * @return CWikiDiscuss
     */
    public function setPublicationId(int $publicationId)
    {
        $this->publicationId = $publicationId;

        return $this;
    }

    /**
     * Get usercId.
     *
     * @return int
     */
    public function getUsercId()
    {
        return $this->usercId;
    }

    /**
     * Set usercId.
     *
     * @return CWikiDiscuss
     */
    public function setUsercId(int $usercId)
    {
        $this->usercId = $usercId;

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
     * Set comment.
     *
     * @return CWikiDiscuss
     */
    public function setComment(string $comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get pScore.
     *
     * @return string
     */
    public function getPScore()
    {
        return $this->pScore;
    }

    /**
     * Set pScore.
     *
     * @return CWikiDiscuss
     */
    public function setPScore(string $pScore)
    {
        $this->pScore = $pScore;

        return $this;
    }

    /**
     * Get dtime.
     *
     * @return DateTime
     */
    public function getDtime()
    {
        return $this->dtime;
    }

    /**
     * Set dtime.
     *
     * @return CWikiDiscuss
     */
    public function setDtime(DateTime $dtime)
    {
        $this->dtime = $dtime;

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

    /**
     * Set cId.
     *
     * @return CWikiDiscuss
     */
    public function setCId(int $cId)
    {
        $this->cId = $cId;

        return $this;
    }
}
