<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEHotpotatoes.
 *
 * @ORM\Table(name="track_e_hotpotatoes", indexes={
 *     @ORM\Index(name="idx_tehp_user_id", columns={"exe_user_id"}),
 *     @ORM\Index(name="idx_tehp_c_id", columns={"c_id"})
 * })
 * @ORM\Entity
 */
class TrackEHotpotatoes
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
     * @ORM\Column(name="exe_name", type="string", length=255, nullable=false)
     */
    protected $exeName;

    /**
     * @var int
     *
     * @ORM\Column(name="exe_user_id", type="integer", nullable=true)
     */
    protected $exeUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="exe_date", type="datetime", nullable=false)
     */
    protected $exeDate;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="score", type="smallint", nullable=false)
     */
    protected $score;

    /**
     * @var int
     *
     * @ORM\Column(name="max_score", type="smallint", nullable=false)
     */
    protected $maxScore;

    /**
     * Set exeName.
     *
     * @param string $exeName
     *
     * @return TrackEHotpotatoes
     */
    public function setExeName($exeName)
    {
        $this->exeName = $exeName;

        return $this;
    }

    /**
     * Get exeName.
     *
     * @return string
     */
    public function getExeName()
    {
        return $this->exeName;
    }

    /**
     * Set exeUserId.
     *
     * @param int $exeUserId
     *
     * @return TrackEHotpotatoes
     */
    public function setExeUserId($exeUserId)
    {
        $this->exeUserId = $exeUserId;

        return $this;
    }

    /**
     * Get exeUserId.
     *
     * @return int
     */
    public function getExeUserId()
    {
        return $this->exeUserId;
    }

    /**
     * Set exeDate.
     *
     * @param \DateTime $exeDate
     *
     * @return TrackEHotpotatoes
     */
    public function setExeDate($exeDate)
    {
        $this->exeDate = $exeDate;

        return $this;
    }

    /**
     * Get exeDate.
     *
     * @return \DateTime
     */
    public function getExeDate()
    {
        return $this->exeDate;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return TrackEHotpotatoes
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
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @param int $score
     *
     * @return TrackEHotpotatoes
     */
    public function setScore(int $score): TrackEHotpotatoes
    {
        $this->score = $score;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxScore(): int
    {
        return $this->maxScore;
    }

    /**
     * @param int $maxScore
     *
     * @return TrackEHotpotatoes
     */
    public function setMaxScore(int $maxScore): TrackEHotpotatoes
    {
        $this->maxScore = $maxScore;

        return $this;
    }
}
