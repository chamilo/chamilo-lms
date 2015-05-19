<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceResource
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\SequenceRepository")
 * @ORM\Table(name="sequence_resource")
 * @ORM\Entity
 */
class SequenceResource
{
    const COURSE_TYPE = 1;
    const SESSION_TYPE = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="resource_id", type="integer")
     */
    private $resourceId;

    /**
     * @var string
     *
     * @ORM\Column(name="graph", type="text")
     */
    private $graph;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
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
        return $this->graph;
    }

    /**
     * @param string $graph
     *
     * @return SequenceResource
     */
    public function setGraph($graph)
    {
        $this->graph = $graph;

        return $this;
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
     */
    public function setResourceId($resourceId)
    {
        $this->resourceId = $resourceId;

        return $this;
    }

}
