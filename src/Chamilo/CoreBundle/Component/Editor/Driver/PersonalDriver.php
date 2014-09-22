<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

/**
 * Class PersonalDriver
 * @todo add more checks in upload/rm
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 */
class PersonalDriver extends Driver
{
    public $name = 'PersonalDriver';

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if ($this->connector->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $userId = $this->connector->user->getUserId();
            if (!empty($userId)) {

                // Adding user personal files
                $dir = \UserManager::get_user_picture_path_by_id($userId, 'system');
                $dirWeb = \UserManager::get_user_picture_path_by_id($userId, 'web');

                $driver = array(
                    'driver' => 'PersonalDriver',
                    'alias' => $this->connector->translator->trans('MyFiles'),
                    'path'       => $dir['dir'].'my_files',
                    'URL' => $dirWeb['dir'].'my_files',
                    'accessControl' => array($this, 'access')
                );
                return $driver;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname)
    {
        $this->setConnectorFromPlugin();
        if ($this->connector->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return parent::upload($fp, $dst, $name, $tmpname);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rm($hash)
    {
        $this->setConnectorFromPlugin();
        if ($this->connector->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return parent::rm($hash);
        }
    }
}
