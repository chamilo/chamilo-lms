<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Composer;

/**
 * Class Tasks
 * Executes tasks when executing composer update/install methods
 * @package ChamiloLMS\CoreBundle\Composer
 */
class Tasks
{
    public static function postInstall()
    {
        /*chmod('../../app/cache', 0777);
        chmod('../../app/config', 0777);
        chmod('../../app/courses', 0777);
        chmod('../../app/logs', 0777);*/
        //chmod('console', 0500);
        //exec('php console assetic:dump');

        //Removing .git folder in vendors
        //system('find ./vendor -name ".git" -exec rm -rf {} \;');
    }

    public static function postUpdate()
    {

    }
}
