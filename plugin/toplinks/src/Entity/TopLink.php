<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\TopLinks;

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

    public function __construct()
    {
        $this->target = '_blank';
        $this->icon = null;
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
}
