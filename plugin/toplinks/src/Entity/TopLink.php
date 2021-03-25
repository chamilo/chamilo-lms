<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\TopLinks;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
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
     * @var string
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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return TopLink
     */
    public function setTitle(string $title): TopLink
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return TopLink
     */
    public function setUrl(string $url): TopLink
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param string $target
     *
     * @return TopLink
     */
    public function setTarget(string $target): TopLink
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     *
     * @return TopLink
     */
    public function setIcon(string $icon): TopLink
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
