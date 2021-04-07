<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\TopLinks;

use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class TopLink.
 *
 * @package Chamilo\PluginBundle\Entity\TopLinks
 *
 * @ORM\Table(name="toplinks_link")
 * @ORM\Entity(repositoryClass="Chamilo\PluginBundle\Entity\TopLinks\Repository\TopLinkRepository")
 */
class TopLink
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string")
     */
    private $title;
    /**
     * @var string
     *
     * @ORM\Column(name="url", type="text")
     */
    private $url;
    /**
     * @var string
     *
     * @ORM\Column(name="target", type="string", length=10, options={"default":"_blank"})
     */
    private $target;
    /**
     * @var string|null
     *
     * @ORM\Column(name="icon", type="string", nullable=true)
     */
    private $icon;
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Chamilo\PluginBundle\Entity\TopLinks\TopLinkRelTool", mappedBy="link", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $tools;

    public function __construct()
    {
        $this->target = '_blank';
        $this->icon = null;
        $this->tools = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): TopLink
    {
        $this->title = $title;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): TopLink
    {
        $this->url = $url;

        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): TopLink
    {
        $this->target = $target;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon = null): TopLink
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTools()
    {
        return $this->tools;
    }

    public function addTool(CTool $tool)
    {
        $linkTool = new TopLinkRelTool();
        $linkTool
            ->setTool($tool)
            ->setLink($this);

        $this->tools->add($linkTool);
    }
}
