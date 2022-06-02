<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceResource.
 *
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\SequenceResourceRepository")
 * @ORM\Table(name="sequence_resource")
 */
class SequenceResource
{
    public const COURSE_TYPE = 1;
    public const SESSION_TYPE = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer")
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="resource_id", type="integer")
     */
    protected $resourceId;

    /**
     * @var Sequence
     *
     * @ORM\ManyToOne(targetEntity="Sequence")
     * @ORM\JoinColumn(name="sequence_id", referencedColumnName="id")
     */
    protected $sequence;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the integer type.
     *
     * @param int $type
     *
     * @return SequenceResource
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getGraph()
    {
        return $this->getSequence()->getGraph();
    }

    /**
     * @return bool
     */
    public function hasGraph()
    {
        $graph = $this->getSequence()->getGraph();

        return !empty($graph) ? true : false;
    }

    /**
     * @return int
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * @param int $resourceId
     *
     * @return $this
     */
    public function setResourceId($resourceId)
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    /**
     * @return Sequence
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @return $this
     */
    public function setSequence(Sequence $sequence)
    {
        $this->sequence = $sequence;

        return $this;
    }
}
