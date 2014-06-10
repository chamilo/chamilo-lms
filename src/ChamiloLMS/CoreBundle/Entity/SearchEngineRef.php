<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchEngineRef
 *
 * @ORM\Table(name="search_engine_ref")
 * @ORM\Entity
 */
class SearchEngineRef
{
    /**
     * @var string
     *
     * @ORM\Column(name="course_code", type="string", length=40, nullable=false)
     */
    private $courseCode;

    /**
     * @var string
     *
     * @ORM\Column(name="tool_id", type="string", length=100, nullable=false)
     */
    private $toolId;

    /**
     * @var integer
     *
     * @ORM\Column(name="ref_id_high_level", type="integer", nullable=false)
     */
    private $refIdHighLevel;

    /**
     * @var integer
     *
     * @ORM\Column(name="ref_id_second_level", type="integer", nullable=true)
     */
    private $refIdSecondLevel;

    /**
     * @var integer
     *
     * @ORM\Column(name="search_did", type="integer", nullable=false)
     */
    private $searchDid;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
