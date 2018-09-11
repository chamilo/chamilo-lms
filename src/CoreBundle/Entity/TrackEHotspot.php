<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\CourseTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEHotspot.
 *
 * @ORM\Table(name="track_e_hotspot", indexes={
 *     @ORM\Index(name="hotspot_user_id", columns={"hotspot_user_id"}),
 *     @ORM\Index(name="hotspot_exe_id", columns={"hotspot_exe_id"}),
 *     @ORM\Index(name="hotspot_question_id", columns={"hotspot_question_id"})
 * })
 * @ORM\Entity
 */
class TrackEHotspot
{
    use CourseTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="hotspot_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $hotspotId;

    /**
     * @var int
     *
     * @ORM\Column(name="hotspot_user_id", type="integer", nullable=false)
     */
    protected $hotspotUserId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="trackEHotspots")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @var int
     *
     * @ORM\Column(name="hotspot_exe_id", type="integer", nullable=false)
     */
    protected $hotspotExeId;

    /**
     * @var int
     *
     * @ORM\Column(name="hotspot_question_id", type="integer", nullable=false)
     */
    protected $hotspotQuestionId;

    /**
     * @var int
     *
     * @ORM\Column(name="hotspot_answer_id", type="integer", nullable=false)
     */
    protected $hotspotAnswerId;

    /**
     * @var bool
     *
     * @ORM\Column(name="hotspot_correct", type="boolean", nullable=false)
     */
    protected $hotspotCorrect;

    /**
     * @var string
     *
     * @ORM\Column(name="hotspot_coordinate", type="text", nullable=false)
     */
    protected $hotspotCoordinate;

    /**
     * Set hotspotUserId.
     *
     * @param int $hotspotUserId
     *
     * @return TrackEHotspot
     */
    public function setHotspotUserId($hotspotUserId)
    {
        $this->hotspotUserId = $hotspotUserId;

        return $this;
    }

    /**
     * Get hotspotUserId.
     *
     * @return int
     */
    public function getHotspotUserId()
    {
        return $this->hotspotUserId;
    }

    /**
     * Set hotspotExeId.
     *
     * @param int $hotspotExeId
     *
     * @return TrackEHotspot
     */
    public function setHotspotExeId($hotspotExeId)
    {
        $this->hotspotExeId = $hotspotExeId;

        return $this;
    }

    /**
     * Get hotspotExeId.
     *
     * @return int
     */
    public function getHotspotExeId()
    {
        return $this->hotspotExeId;
    }

    /**
     * Set hotspotQuestionId.
     *
     * @param int $hotspotQuestionId
     *
     * @return TrackEHotspot
     */
    public function setHotspotQuestionId($hotspotQuestionId)
    {
        $this->hotspotQuestionId = $hotspotQuestionId;

        return $this;
    }

    /**
     * Get hotspotQuestionId.
     *
     * @return int
     */
    public function getHotspotQuestionId()
    {
        return $this->hotspotQuestionId;
    }

    /**
     * Set hotspotAnswerId.
     *
     * @param int $hotspotAnswerId
     *
     * @return TrackEHotspot
     */
    public function setHotspotAnswerId($hotspotAnswerId)
    {
        $this->hotspotAnswerId = $hotspotAnswerId;

        return $this;
    }

    /**
     * Get hotspotAnswerId.
     *
     * @return int
     */
    public function getHotspotAnswerId()
    {
        return $this->hotspotAnswerId;
    }

    /**
     * Set hotspotCorrect.
     *
     * @param bool $hotspotCorrect
     *
     * @return TrackEHotspot
     */
    public function setHotspotCorrect($hotspotCorrect)
    {
        $this->hotspotCorrect = $hotspotCorrect;

        return $this;
    }

    /**
     * Get hotspotCorrect.
     *
     * @return bool
     */
    public function getHotspotCorrect()
    {
        return $this->hotspotCorrect;
    }

    /**
     * Set hotspotCoordinate.
     *
     * @param string $hotspotCoordinate
     *
     * @return TrackEHotspot
     */
    public function setHotspotCoordinate($hotspotCoordinate)
    {
        $this->hotspotCoordinate = $hotspotCoordinate;

        return $this;
    }

    /**
     * Get hotspotCoordinate.
     *
     * @return string
     */
    public function getHotspotCoordinate()
    {
        return $this->hotspotCoordinate;
    }

    /**
     * Get hotspotId.
     *
     * @return int
     */
    public function getHotspotId()
    {
        return $this->hotspotId;
    }
}
