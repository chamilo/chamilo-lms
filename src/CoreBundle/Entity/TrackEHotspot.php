<?php

declare(strict_types=1);

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
     * @ORM\Column(name="hotspot_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $hotspotId;

    /**
     * @ORM\Column(name="hotspot_user_id", type="integer", nullable=false)
     */
    protected int $hotspotUserId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="trackEHotspots")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected ?\Chamilo\CoreBundle\Entity\Course $course = null;

    /**
     * @ORM\Column(name="hotspot_exe_id", type="integer", nullable=false)
     */
    protected int $hotspotExeId;

    /**
     * @ORM\Column(name="hotspot_question_id", type="integer", nullable=false)
     */
    protected int $hotspotQuestionId;

    /**
     * @ORM\Column(name="hotspot_answer_id", type="integer", nullable=false)
     */
    protected int $hotspotAnswerId;

    /**
     * @ORM\Column(name="hotspot_correct", type="boolean", nullable=false)
     */
    protected bool $hotspotCorrect;

    /**
     * @ORM\Column(name="hotspot_coordinate", type="text", nullable=false)
     */
    protected string $hotspotCoordinate;

    /**
     * Set hotspotUserId.
     *
     * @return TrackEHotspot
     */
    public function setHotspotUserId(int $hotspotUserId)
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
     * @return TrackEHotspot
     */
    public function setHotspotExeId(int $hotspotExeId)
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
     * @return TrackEHotspot
     */
    public function setHotspotQuestionId(int $hotspotQuestionId)
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
     * @return TrackEHotspot
     */
    public function setHotspotAnswerId(int $hotspotAnswerId)
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
     * @return TrackEHotspot
     */
    public function setHotspotCorrect(bool $hotspotCorrect)
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
     * @return TrackEHotspot
     */
    public function setHotspotCoordinate(string $hotspotCoordinate)
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
