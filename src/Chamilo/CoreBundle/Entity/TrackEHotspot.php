<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEHotspot
 *
 * @ORM\Table(name="track_e_hotspot", indexes={@ORM\Index(name="hotspot_user_id", columns={"hotspot_user_id"}), @ORM\Index(name="hotspot_exe_id", columns={"hotspot_exe_id"}), @ORM\Index(name="hotspot_question_id", columns={"hotspot_question_id"})})
 * @ORM\Entity
 */
class TrackEHotspot
{
    /**
     * @var integer
     *
     * @ORM\Column(name="hotspot_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $hotspotId;

    /**
     * @var integer
     *
     * @ORM\Column(name="hotspot_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotUserId;

    /**
     * @var integer
     *
     * @ORM\Column(name="hotspot_exe_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotExeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="hotspot_question_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotQuestionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="hotspot_answer_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotAnswerId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hotspot_correct", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotCorrect;

    /**
     * @var string
     *
     * @ORM\Column(name="hotspot_coordinate", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hotspotCoordinate;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;


    /**
     * Get hotspotId
     *
     * @return integer
     */
    public function getHotspotId()
    {
        return $this->hotspotId;
    }

    /**
     * Set hotspotUserId
     *
     * @param integer $hotspotUserId
     * @return TrackEHotspot
     */
    public function setHotspotUserId($hotspotUserId)
    {
        $this->hotspotUserId = $hotspotUserId;

        return $this;
    }

    /**
     * Get hotspotUserId
     *
     * @return integer
     */
    public function getHotspotUserId()
    {
        return $this->hotspotUserId;
    }

    /**
     * Set hotspotExeId
     *
     * @param integer $hotspotExeId
     * @return TrackEHotspot
     */
    public function setHotspotExeId($hotspotExeId)
    {
        $this->hotspotExeId = $hotspotExeId;

        return $this;
    }

    /**
     * Get hotspotExeId
     *
     * @return integer
     */
    public function getHotspotExeId()
    {
        return $this->hotspotExeId;
    }

    /**
     * Set hotspotQuestionId
     *
     * @param integer $hotspotQuestionId
     * @return TrackEHotspot
     */
    public function setHotspotQuestionId($hotspotQuestionId)
    {
        $this->hotspotQuestionId = $hotspotQuestionId;

        return $this;
    }

    /**
     * Get hotspotQuestionId
     *
     * @return integer
     */
    public function getHotspotQuestionId()
    {
        return $this->hotspotQuestionId;
    }

    /**
     * Set hotspotAnswerId
     *
     * @param integer $hotspotAnswerId
     * @return TrackEHotspot
     */
    public function setHotspotAnswerId($hotspotAnswerId)
    {
        $this->hotspotAnswerId = $hotspotAnswerId;

        return $this;
    }

    /**
     * Get hotspotAnswerId
     *
     * @return integer
     */
    public function getHotspotAnswerId()
    {
        return $this->hotspotAnswerId;
    }

    /**
     * Set hotspotCorrect
     *
     * @param boolean $hotspotCorrect
     * @return TrackEHotspot
     */
    public function setHotspotCorrect($hotspotCorrect)
    {
        $this->hotspotCorrect = $hotspotCorrect;

        return $this;
    }

    /**
     * Get hotspotCorrect
     *
     * @return boolean
     */
    public function getHotspotCorrect()
    {
        return $this->hotspotCorrect;
    }

    /**
     * Set hotspotCoordinate
     *
     * @param string $hotspotCoordinate
     * @return TrackEHotspot
     */
    public function setHotspotCoordinate($hotspotCoordinate)
    {
        $this->hotspotCoordinate = $hotspotCoordinate;

        return $this;
    }

    /**
     * Get hotspotCoordinate
     *
     * @return string
     */
    public function getHotspotCoordinate()
    {
        return $this->hotspotCoordinate;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return TrackEHotspot
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

