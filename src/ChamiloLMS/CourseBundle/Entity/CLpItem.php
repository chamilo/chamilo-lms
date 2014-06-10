<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLpItem
 *
 * @ORM\Table(name="c_lp_item", indexes={@ORM\Index(name="lp_id", columns={"lp_id"}), @ORM\Index(name="idx_c_lp_item_cid_lp_id", columns={"c_id", "lp_id"})})
 * @ORM\Entity
 */
class CLpItem
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="lp_id", type="integer", nullable=false)
     */
    private $lpId;

    /**
     * @var string
     *
     * @ORM\Column(name="item_type", type="string", length=32, nullable=false)
     */
    private $itemType;

    /**
     * @var string
     *
     * @ORM\Column(name="ref", type="text", nullable=false)
     */
    private $ref;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=511, nullable=false)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=511, nullable=false)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="text", nullable=false)
     */
    private $path;

    /**
     * @var float
     *
     * @ORM\Column(name="min_score", type="float", precision=10, scale=0, nullable=false)
     */
    private $minScore;

    /**
     * @var float
     *
     * @ORM\Column(name="max_score", type="float", precision=10, scale=0, nullable=true)
     */
    private $maxScore;

    /**
     * @var float
     *
     * @ORM\Column(name="mastery_score", type="float", precision=10, scale=0, nullable=true)
     */
    private $masteryScore;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_item_id", type="integer", nullable=false)
     */
    private $parentItemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="previous_item_id", type="integer", nullable=false)
     */
    private $previousItemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="next_item_id", type="integer", nullable=false)
     */
    private $nextItemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="display_order", type="integer", nullable=false)
     */
    private $displayOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="prerequisite", type="text", nullable=true)
     */
    private $prerequisite;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text", nullable=true)
     */
    private $parameters;

    /**
     * @var string
     *
     * @ORM\Column(name="launch_data", type="text", nullable=false)
     */
    private $launchData;

    /**
     * @var string
     *
     * @ORM\Column(name="max_time_allowed", type="string", length=13, nullable=true)
     */
    private $maxTimeAllowed;

    /**
     * @var string
     *
     * @ORM\Column(name="terms", type="text", nullable=true)
     */
    private $terms;

    /**
     * @var integer
     *
     * @ORM\Column(name="search_did", type="integer", nullable=true)
     */
    private $searchDid;

    /**
     * @var string
     *
     * @ORM\Column(name="audio", type="string", length=250, nullable=true)
     */
    private $audio;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
