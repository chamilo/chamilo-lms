<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\Driver;

/**
 * Class HomeDriver
 * @package ChamiloLMS\Component\Editor\Driver
 */
class HomeDriver extends Driver
{
    public $name = 'HomeDriver';

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if ($this->connector->security->isGranted('ROLE_ADMIN')) {
            $home = api_get_path(SYS_DATA_PATH).'home';
            return array(
                'driver'     => 'HomeDriver',
                'alias' => $this->connector->translator->trans('Portal'),
                'path'       => $home,
                'startPath'  => '/',
                'URL' => api_get_path(WEB_DATA_PATH).'home',
                'accessControl' => array($this, 'access'),
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname)
    {
        // error_log(intval($this->connector->security->isGranted('ROLE_ADMIN')));
        // can't use $this->connector->security
        if (api_is_platform_admin()) {
            return parent::upload($fp, $dst, $name, $tmpname);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rm($hash)
    {
        if (api_is_platform_admin()) {
            return parent::rm($hash);
        }
    }
}
