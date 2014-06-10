<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpItemView
 *
 * @ORM\Table(name="c_lp_item_view", indexes={@ORM\Index(name="lp_item_id", columns={"lp_item_id"}), @ORM\Index(name="lp_view_id", columns={"lp_view_id"}), @ORM\Index(name="idx_c_lp_item_view_cid_lp_view_id_lp_item_id", columns={"c_id", "lp_view_id", "lp_item_id"})})
 * @ORM\Entity
 */
class CLpItemView
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
     * @ORM\Column(name="id", type="bigint", nullable=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="lp_item_id", type="integer", nullable=false)
     */
    private $lpItemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="lp_view_id", type="integer", nullable=false)
     */
    private $lpViewId;

    /**
     * @var integer
     *
     * @ORM\Column(name="view_count", type="integer", nullable=false)
     */
    private $viewCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="integer", nullable=false)
     */
    private $startTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_time", type="integer", nullable=false)
     */
    private $totalTime;

    /**
     * @var float
     *
     * @ORM\Column(name="score", type="float", precision=10, scale=0, nullable=false)
     */
    private $score;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=32, nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="suspend_data", type="text", nullable=true)
     */
    private $suspendData;

    /**
     * @var string
     *
     * @ORM\Column(name="lesson_location", type="text", nullable=true)
     */
    private $lessonLocation;

    /**
     * @var string
     *
     * @ORM\Column(name="core_exit", type="string", length=32, nullable=false)
     */
    private $coreExit;

    /**
     * @var string
     *
     * @ORM\Column(name="max_score", type="string", length=8, nullable=true)
     */
    private $maxScore;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
