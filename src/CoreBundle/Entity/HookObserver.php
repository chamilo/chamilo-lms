<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HookObserver.
 *
 * @ORM\Table(
 *     name="hook_observer",
 *     options={"row_format"="DYNAMIC"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="class_name", columns={"class_name"})
 *     }
 * )
 * @ORM\Entity
 */
class HookObserver
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\Column(name="class_name", type="string", length=190, nullable=true)
     */
    protected ?string $className = null;

    /**
     * @ORM\Column(name="path", type="string", length=255, nullable=false)
     */
    protected string $path;

    /**
     * @ORM\Column(name="plugin_name", type="string", length=255, nullable=true)
     */
    protected ?string $pluginName = null;

    /**
     * Set className.
     *
     * @return HookObserver
     */
    public function setClassName(string $className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Get className.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Set path.
     *
     * @return HookObserver
     */
    public function setPath(string $path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set pluginName.
     *
     * @return HookObserver
     */
    public function setPluginName(string $pluginName)
    {
        $this->pluginName = $pluginName;

        return $this;
    }

    /**
     * Get pluginName.
     *
     * @return string
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
