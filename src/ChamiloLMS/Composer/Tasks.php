<?php

namespace ChamiloLMS\Composer;

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
    }

    public static function postUpdate()
    {

    }
}