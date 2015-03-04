<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

use Chamilo\CoreBundle\Component\Editor\Connector;

/**
 * Class DriverInterface
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 */
interface DriverInterface
{
    /**
     * Gets driver name.
     * @return string
     */
    public function getName();

    /**
     * Gets driver name.
     * @param string $name
     */
    public function setName($name);

    /**
     * Set connector
     * @param Connector $connector
     */
    public function setConnector(Connector $connector);

    /**
     * @return array
     */
    public function getAppPluginOptions();

    /**
     * @return Connector
     */
    public function setConnectorFromPlugin();

    /**
     * @return bool
     */
    public function allow();

    public function getConfiguration();
    public function setup();
}
