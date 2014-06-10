<?php

namespace ChamiloLMS\CoreBundle\Entity;

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
     * @ORM\Column(name="hotspot_user_id", type="integer", nullable=false)
     */
    private $hotspotUserId;

    /**
     * @var integer
     *
     * @ORM\Column(name="hotspot_exe_id", type="integer", nullable=false)
     */
    private $hotspotExeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="hotspot_question_id", type="integer", nullable=false)
     */
    private $hotspotQuestionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="hotspot_answer_id", type="integer", nullable=false)
     */
    private $hotspotAnswerId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hotspot_correct", type="boolean", nullable=false)
     */
    private $hotspotCorrect;

    /**
     * @var string
     *
     * @ORM\Column(name="hotspot_coordinate", type="text", nullable=false)
     */
    private $hotspotCoordinate;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="hotspot_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $hotspotId;


}
