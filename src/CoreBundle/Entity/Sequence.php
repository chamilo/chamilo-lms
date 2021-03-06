<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fhaculty\Graph\Graph;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Class Sequence.
 *
 * @ORM\Table(name="sequence")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\SequenceRepository")
 */
class Sequence
{
    use TimestampableEntity;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\Column(name="name", type="string")
     */
    protected string $name;

    /**
     * @ORM\Column(name="graph", type="text", nullable=true)
     */
    protected ?string $graph = null;

    public function __toString(): string
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Sequence
     */
    public function setName(string $name)
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
     * @return Sequence
     */
    public function setGraph(string $graph)
    {
        $this->graph = $graph;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasGraph()
    {
        return !empty($this->graph);
    }

    /**
     * @return Graph
     */
    public function getUnSerializeGraph()
    {
        return unserialize($this->graph);
    }

    public function setGraphAndSerialize($graph): self
    {
        $this->setGraph(serialize($graph));

        return $this;
    }
}
