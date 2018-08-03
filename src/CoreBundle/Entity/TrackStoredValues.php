<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackStoredValues.
 *
 * @ORM\Table(
 *  name="track_stored_values",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(name="user_id_2", columns={"user_id", "sco_id", "course_id", "sv_key"})
 *  },
 *  indexes={
 *      @ORM\Index(name="user_sco_course_sv", columns={"user_id", "sco_id", "course_id", "sv_key"})
 *  }
 * )
 * @ORM\Entity
 */
class TrackStoredValues
{
    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="sco_id", type="integer", nullable=false)
     */
    protected $scoId;

    /**
     * @var string
     *
     * @ORM\Column(name="course_id", type="string", length=40, nullable=false)
     */
    protected $courseId;

    /**
     * @var string
     *
     * @ORM\Column(name="sv_key", type="string", length=64, nullable=false)
     */
    protected $svKey;

    /**
     * @var string
     *
     * @ORM\Column(name="sv_value", type="text", nullable=false)
     */
    protected $svValue;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return TrackStoredValues
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set scoId.
     *
     * @param int $scoId
     *
     * @return TrackStoredValues
     */
    public function setScoId($scoId)
    {
        $this->scoId = $scoId;

        return $this;
    }

    /**
     * Get scoId.
     *
     * @return int
     */
    public function getScoId()
    {
        return $this->scoId;
    }

    /**
     * Set courseId.
     *
     * @param string $courseId
     *
     * @return TrackStoredValues
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * Get courseId.
     *
     * @return string
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * Set svKey.
     *
     * @param string $svKey
     *
     * @return TrackStoredValues
     */
    public function setSvKey($svKey)
    {
        $this->svKey = $svKey;

        return $this;
    }

    /**
     * Get svKey.
     *
     * @return string
     */
    public function getSvKey()
    {
        return $this->svKey;
    }

    /**
     * Set svValue.
     *
     * @param string $svValue
     *
     * @return TrackStoredValues
     */
    public function setSvValue($svValue)
    {
        $this->svValue = $svValue;

        return $this;
    }

    /**
     * Get svValue.
     *
     * @return string
     */
    public function getSvValue()
    {
        return $this->svValue;
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
}
