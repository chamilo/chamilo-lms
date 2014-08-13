<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

use Chamilo\CoreBundle\Component\Editor\Connector;

/**
 * Class Driver
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 */
class Driver extends \elFinderVolumeLocalFileSystem implements InterfaceDriver
{
    /** @var string */
    public $name;

    /** @var Connector */
    public $connector;

    /**
     * Gets driver name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets driver name.
     * @param string
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set connector
     * @param Connector $connector
     */
    public function setConnector(Connector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * @return array
     */
    public function getAppPluginOptions()
    {
        return $this->getOptionsPlugin('chamilo');
    }

    /**
     * @return Connector
     */
    public function setConnectorFromPlugin()
    {
        $options = $this->getAppPluginOptions();
        $this->setConnector($options['connector']);
    }
}
