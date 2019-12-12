<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\ImsLti;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Deployment.
 *
 * @package Chamilo\PluginBundle\Entity\ImsLti
 *
 * @ORM\Table(name="plugin_ims_lti_deployment")
 * @ORM\Entity()
 */
class Deployment
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     */
    protected $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    public $deployId;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Deployment
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
