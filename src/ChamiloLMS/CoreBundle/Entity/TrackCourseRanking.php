<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackCourseRanking
 *
 * @ORM\Table(name="track_course_ranking", indexes={@ORM\Index(name="idx_tcc_cid", columns={"c_id"}), @ORM\Index(name="idx_tcc_sid", columns={"session_id"}), @ORM\Index(name="idx_tcc_urlid", columns={"url_id"}), @ORM\Index(name="idx_tcc_creation_date", columns={"creation_date"})})
 * @ORM\Entity
 */
class TrackCourseRanking
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="url_id", type="integer", nullable=false)
     */
    private $urlId;

    /**
     * @var integer
     *
     * @ORM\Column(name="accesses", type="integer", nullable=false)
     */
    private $accesses;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_score", type="integer", nullable=false)
     */
    private $totalScore;

    /**
     * @var integer
     *
     * @ORM\Column(name="users", type="integer", nullable=false)
     */
    private $users;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
