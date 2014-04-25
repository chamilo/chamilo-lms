<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Component\Editor\Driver;

use ChamiloLMS\Component\Editor\Connector;

/**
 * Class Driver
 * @package ChamiloLMS\Component\Editor\Driver
 */
interface interfaceDriver
{
    /**
     * Gets driver name.
     * @return string
     */
    public function getName();

    /**
     * Gets driver name.
     * @param string
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
}
