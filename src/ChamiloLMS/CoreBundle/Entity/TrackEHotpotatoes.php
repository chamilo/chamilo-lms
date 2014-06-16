<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEHotpotatoes
 *
 * @ORM\Table(name="track_e_hotpotatoes")
 * @ORM\Entity
 */
class TrackEHotpotatoes
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="exe_name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeName;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $exeUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="exe_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_result", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeResult;

    /**
     * @var integer
     *
     * @ORM\Column(name="exe_weighting", type="smallint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $exeWeighting;


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
     * Set exeName
     *
     * @param string $exeName
     * @return TrackEHotpotatoes
     */
    public function setExeName($exeName)
    {
        $this->exeName = $exeName;

        return $this;
    }

    /**
     * Get exeName
     *
     * @return string
     */
    public function getExeName()
    {
        return $this->exeName;
    }

    /**
     * Set exeUserId
     *
     * @param integer $exeUserId
     * @return TrackEHotpotatoes
     */
    public function setExeUserId($exeUserId)
    {
        $this->exeUserId = $exeUserId;

        return $this;
    }

    /**
     * Get exeUserId
     *
     * @return integer
     */
    public function getExeUserId()
    {
        return $this->exeUserId;
    }

    /**
     * Set exeDate
     *
     * @param \DateTime $exeDate
     * @return TrackEHotpotatoes
     */
    public function setExeDate($exeDate)
    {
        $this->exeDate = $exeDate;

        return $this;
    }

    /**
     * Get exeDate
     *
     * @return \DateTime
     */
    public function getExeDate()
    {
        return $this->exeDate;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return TrackEHotpotatoes
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
     * Set exeResult
     *
     * @param integer $exeResult
     * @return TrackEHotpotatoes
     */
    public function setExeResult($exeResult)
    {
        $this->exeResult = $exeResult;

        return $this;
    }

    /**
     * Get exeResult
     *
     * @return integer
     */
    public function getExeResult()
    {
        return $this->exeResult;
    }

    /**
     * Set exeWeighting
     *
     * @param integer $exeWeighting
     * @return TrackEHotpotatoes
     */
    public function setExeWeighting($exeWeighting)
    {
        $this->exeWeighting = $exeWeighting;

        return $this;
    }

    /**
     * Get exeWeighting
     *
     * @return integer
     */
    public function getExeWeighting()
    {
        return $this->exeWeighting;
    }
}
