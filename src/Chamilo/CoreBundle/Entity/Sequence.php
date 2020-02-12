<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fhaculty\Graph\Graph;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class Sequence.
 *
 * @ORM\Table(name="sequence")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\SequenceRepository")
 */
class Sequence
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="graph", type="text", nullable=true)
     */
    protected $graph;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Sequence
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Sequence
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * @return Sequence
     */
    public function setGraph($graph)
    {
        $this->graph = $graph;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasGraph()
    {
        return !empty($this->graph) ? true : false;
    }

    /**
     * @return Graph
     */
    public function getUnSerializeGraph()
    {
        return \UnserializeApi::unserialize('sequence_graph', $this->graph);
    }

    /**
     * @param string $graph
     *
     * @return Sequence
     */
    public function setGraphAndSerialize($graph)
    {
        $this->setGraph(serialize($graph));

        return $this;
    }
}
