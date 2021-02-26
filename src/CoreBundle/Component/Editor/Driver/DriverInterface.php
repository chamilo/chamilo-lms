<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

use Chamilo\CoreBundle\Component\Editor\Connector;

interface DriverInterface
{
    public function setup();

    /**
     * Gets driver name.
     *
     * @return string
     */
    public function getName();

    /**
     * Gets driver name.
     *
     * @param string $name
     */
    public function setName($name);

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
}
