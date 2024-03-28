<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\TopLinks;

use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class TopLinkRelTool.
 *
 * @package Chamilo\PluginBundle\Entity\TopLinks
 *
 * @ORM\Table(name="toplinks_link_rel_tool")
 * @ORM\Entity(repositoryClass="Chamilo\PluginBundle\Entity\TopLinks\Repository\TopLinkRelToolRepository")
 */
class TopLinkRelTool
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
     * @var \Chamilo\PluginBundle\Entity\TopLinks\TopLink
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\PluginBundle\Entity\TopLinks\TopLink", inversedBy="tools")
     * @ORM\JoinColumn(name="link_id", referencedColumnName="id")
     */
    private $link;
    /**
     * @var \Chamilo\CourseBundle\Entity\CTool
     *
     * @ORM\OneToOne(targetEntity="Chamilo\CourseBundle\Entity\CTool", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="iid", nullable=true, onDelete="CASCADE")
     */
    private $tool;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): TopLinkRelTool
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return \Chamilo\PluginBundle\Entity\TopLinks\TopLink
     */
    public function getLink(): TopLink
    {
        return $this->link;
    }

    /**
     * @param \Chamilo\PluginBundle\Entity\TopLinks\TopLink $link
     */
    public function setLink(TopLink $link): TopLinkRelTool
    {
        $this->link = $link;

        return $this;
    }

    public function getTool(): CTool
    {
        return $this->tool;
    }

    public function setTool(CTool $tool): TopLinkRelTool
    {
        $this->tool = $tool;

        return $this;
    }
}
