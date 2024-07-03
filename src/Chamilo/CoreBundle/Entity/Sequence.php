<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Fhaculty\Graph\Graph;
use Gedmo\Mapping\Annotation as Gedmo;
use UnserializeApi;

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
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var DateTime
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

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Sequence
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Sequence
    {
        $this->name = $name;

        return $this;
    }

    public function getGraph(): string
    {
        return $this->graph;
    }

    public function setGraph(string $graph): Sequence
    {
        $this->graph = $graph;

        return $this;
    }

    public function hasGraph(): bool
    {
        return !empty($this->graph);
    }

    public function getUnSerializeGraph(): Graph
    {
        return UnserializeApi::unserialize('sequence_graph', $this->graph);
    }

    public function setGraphAndSerialize(Graph $graph): Sequence
    {
        $this->setGraph(serialize($graph));

        return $this;
    }
}
