<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\TopLinks\Entity;

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\PluginBundle\TopLinks\Entity\Repository\TopLinkRelToolRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'toplinks_link_rel_tool')]
#[ORM\Entity(repositoryClass: TopLinkRelToolRepository::class)]
class TopLinkRelTool
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id;
    #[ORM\ManyToOne(targetEntity: TopLink::class, inversedBy: 'tools')]
    #[ORM\JoinColumn(name: 'link_id', referencedColumnName: 'id')]
    private ?TopLink $link;

    #[ORM\OneToOne(targetEntity: CTool::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'tool_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    private ?CTool $tool;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): TopLinkRelTool
    {
        $this->id = $id;

        return $this;
    }

    public function getLink(): ?TopLink
    {
        return $this->link;
    }

    public function setLink(TopLink $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getTool(): CTool
    {
        return $this->tool;
    }

    public function setTool(CTool $tool): static
    {
        $this->tool = $tool;

        return $this;
    }
}
