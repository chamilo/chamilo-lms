<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

/**
 * Class HomeDriver
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 */
class HomeDriver extends Driver
{
    public $name = 'HomeDriver';

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        //if ($this->connector->security->isGranted('ROLE_ADMIN')) {
        if (api_is_platform_admin()) {
            $home = api_get_path(SYS_PATH).'home';

            return array(
                'driver' => 'HomeDriver',
                'alias' => get_lang('Portal'),
                'path' => $home,
                'URL' => api_get_path(WEB_PATH) . 'home',
                'accessControl' => array($this, 'access'),
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname)
    {
        $this->setConnectorFromPlugin();
        if ($this->connector->security->isGranted('ROLE_ADMIN')) {
            return parent::upload($fp, $dst, $name, $tmpname);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rm($hash)
    {
        $this->setConnectorFromPlugin();
        if ($this->connector->security->isGranted('ROLE_ADMIN')) {
            return parent::rm($hash);
        }
    }
}
