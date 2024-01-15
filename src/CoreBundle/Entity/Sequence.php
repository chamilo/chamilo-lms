<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\SequenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Fhaculty\Graph\Graph;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;

#[ORM\Table(name: 'sequence')]
#[ORM\Entity(repositoryClass: SequenceRepository::class)]
class Sequence implements Stringable
{
    use TimestampableEntity;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'title', type: 'string')]
    protected string $title;

    #[ORM\Column(name: 'graph', type: 'text', nullable: true)]
    protected ?string $graph = null;

    public function __toString(): string
    {
        return $this->title;
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
    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getGraph()
    {
        return $this->graph;
    }

    public function setGraph(string $graph): self
    {
        $this->graph = $graph;

        return $this;
    }

    public function hasGraph(): bool
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

    public function setGraphAndSerialize(Graph $graph): self
    {
        $this->setGraph(serialize($graph));

        return $this;
    }
}
