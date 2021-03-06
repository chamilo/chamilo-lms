<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SearchEngineRef.
 *
 * @ORM\Table(name="search_engine_ref")
 * @ORM\Entity
 */
class SearchEngineRef
{
    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="searchEngineRefs")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected ?\Chamilo\CoreBundle\Entity\Course $course = null;

    /**
     * @ORM\Column(name="tool_id", type="string", length=100, nullable=false)
     */
    protected string $toolId;

    /**
     * @ORM\Column(name="ref_id_high_level", type="integer", nullable=false)
     */
    protected int $refIdHighLevel;

    /**
     * @ORM\Column(name="ref_id_second_level", type="integer", nullable=true)
     */
    protected ?int $refIdSecondLevel = null;

    /**
     * @ORM\Column(name="search_did", type="integer", nullable=false)
     */
    protected int $searchDid;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * Set course.
     *
     * @param \Chamilo\CoreBundle\Entity\Course $course
     *
     * @return \Chamilo\CoreBundle\Entity\SearchEngineRef
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Get course.
     *
     * @return \Chamilo\CoreBundle\Entity\Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * Set toolId.
     *
     * @return SearchEngineRef
     */
    public function setToolId(string $toolId)
    {
        $this->toolId = $toolId;

        return $this;
    }

    /**
     * Get toolId.
     *
     * @return string
     */
    public function getToolId()
    {
        return $this->toolId;
    }

    /**
     * Set refIdHighLevel.
     *
     * @return SearchEngineRef
     */
    public function setRefIdHighLevel(int $refIdHighLevel)
    {
        $this->refIdHighLevel = $refIdHighLevel;

        return $this;
    }

    /**
     * Get refIdHighLevel.
     *
     * @return int
     */
    public function getRefIdHighLevel()
    {
        return $this->refIdHighLevel;
    }

    /**
     * Set refIdSecondLevel.
     *
     * @return SearchEngineRef
     */
    public function setRefIdSecondLevel(int $refIdSecondLevel)
    {
        $this->refIdSecondLevel = $refIdSecondLevel;

        return $this;
    }

    /**
     * Get refIdSecondLevel.
     *
     * @return int
     */
    public function getRefIdSecondLevel()
    {
        return $this->refIdSecondLevel;
    }

    /**
     * Set searchDid.
     *
     * @return SearchEngineRef
     */
    public function setSearchDid(int $searchDid)
    {
        $this->searchDid = $searchDid;

        return $this;
    }

    /**
     * Get searchDid.
     *
     * @return int
     */
    public function getSearchDid()
    {
        return $this->searchDid;
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
