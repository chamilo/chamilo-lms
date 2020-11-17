<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

/**
 * Class HomeDriver.
 *
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 */
class HomeDriver extends Driver implements DriverInterface
{
    public $name = 'HomeDriver';

    /**
     * {@inheritdoc}
     */
    public function setup()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if ($this->allow()) {
            $home = api_get_path(SYS_HOME_PATH);

            return [
                'driver' => 'HomeDriver',
                'alias' => get_lang('Portal'),
                'path' => $home,
                'URL' => api_get_path(WEB_PATH).'home',
                'accessControl' => [$this, 'access'],
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname)
    {
        $this->setConnectorFromPlugin();

        if ($this->allow()) {
            return parent::upload($fp, $dst, $name, $tmpname);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rm($hash)
    {
        $this->setConnectorFromPlugin();

        if ($this->allow()) {
            return parent::rm($hash);
        }
    }

    /**
     * @return bool
     */
    public function allow()
    {
        //if ($this->connector->security->isGranted('ROLE_ADMIN')) {
        return api_is_platform_admin();
    }
}
