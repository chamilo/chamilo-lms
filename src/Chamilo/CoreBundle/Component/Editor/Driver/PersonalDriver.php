<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

/**
 * Class PersonalDriver
 * @todo add more checks in upload/rm
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 */
class PersonalDriver extends Driver implements DriverInterface
{
    public $name = 'PersonalDriver';

    /**
     * @inheritdoc
     */
    public function setup()
    {
        $userId = api_get_user_id();
        $dir = \UserManager::getUserPathById($userId, 'system');
        if (!empty($dir)) {

            if (!is_dir($dir)) {
                mkdir($dir);
            }

            if (!is_dir($dir . 'my_files')) {
                mkdir($dir . 'my_files');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if ($this->allow()) {

            $userId = api_get_user_id();

            if (!empty($userId)) {

                // Adding user personal files
                $dir = \UserManager::getUserPathById($userId, 'system');
                $dirWeb = \UserManager::getUserPathById($userId, 'web');

                $driver = array(
                    'driver' => 'PersonalDriver',
                    'alias' => get_lang('MyFiles'),
                    'path' => $dir.'my_files',
                    'URL' => $dirWeb.'my_files',
                    'accessControl' => array($this, 'access'),
                    'disabled' => array(
                        'duplicate',
                        //'rename',
                        //'mkdir',
                        'mkfile',
                        'copy',
                        'cut',
                        'paste',
                        'edit',
                        'extract',
                        'archive',
                        'help',
                        'resize'
                    ),
                );

                return $driver;
            }
        }

        return array();
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
        //if ($this->connector->security->isGranted('IS_AUTHENTICATED_FULLY')) {

        return !api_is_anonymous();
    }
}
