<?php

/* For license terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\TopLinks\Entity;

use Chamilo\CourseBundle\Entity\CShortcut;
use Chamilo\PluginBundle\TopLinks\Entity\Repository\TopLinkRelShortcutRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'toplinks_link_rel_shortcut')]
#[ORM\Entity(repositoryClass: TopLinkRelShortcutRepository::class)]
class TopLinkRelShortcut
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TopLink::class)]
    #[ORM\JoinColumn(name: 'link_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?TopLink $link = null;

    #[ORM\OneToOne(targetEntity: CShortcut::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'shortcut_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?CShortcut $shortcut = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getShortcut(): ?CShortcut
    {
        return $this->shortcut;
    }

    public function setShortcut(CShortcut $shortcut): static
    {
        $this->shortcut = $shortcut;

        return $this;
    }
}
