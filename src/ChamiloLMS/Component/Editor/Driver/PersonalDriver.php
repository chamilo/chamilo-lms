<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\Driver;

/**
 * Class PersonalDriver
 * @package ChamiloLMS\Component\Editor\Driver
 */
class PersonalDriver extends Driver
{
    public $name = 'PersonalDriver';

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $userId = $this->connector->user->getUserId();

        if (!empty($userId)) {

            // Adding user personal files
            $dir = \UserManager::get_user_picture_path_by_id($userId, 'system');
            $dirWeb = \UserManager::get_user_picture_path_by_id($userId, 'web');

            $driver = array(
                'driver' => 'PersonalDriver',
                'alias' => $this->connector->translator->trans('MyFiles'),
                'path'       => $dir['dir'].'my_files',
                'startPath'  => '/',
                'URL' => $dirWeb['dir'].'my_files',
                'accessControl' => array($this, 'access'),
            );
            return $driver;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname)
    {
        return parent::upload($fp, $dst, $name, $tmpname);
    }

    /**
     * {@inheritdoc}
     */
    public function rm($hash)
    {
        return parent::rm($hash);
    }
}
