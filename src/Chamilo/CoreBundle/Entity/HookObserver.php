<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HookObserver
 *
 * @ORM\Table(name="hook_observer", uniqueConstraints={@ORM\UniqueConstraint(name="class_name", columns={"class_name"})})
 * @ORM\Entity
 */
class HookObserver
{
    /**
     * @var string
     *
     * @ORM\Column(name="class_name", type="string", length=255, nullable=true)
     */
    private $className;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255, nullable=false)
     */
    private $path;

    /**
     * @var string
     *
     * @ORM\Column(name="plugin_name", type="string", length=255, nullable=true)
     */
    private $pluginName;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set className
     *
     * @param string $className
     * @return HookObserver
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Get className
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return HookObserver
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set pluginName
     *
     * @param string $pluginName
     * @return HookObserver
     */
    public function setPluginName($pluginName)
    {
        $this->pluginName = $pluginName;

        return $this;
    }

    /**
     * Get pluginName
     *
     * @return string
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
