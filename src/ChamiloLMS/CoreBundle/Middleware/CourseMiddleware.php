<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Middleware;

/**
 * Class CourseMiddleware
 * @package ChamiloLMS\CoreBundle\Middleware
 */
class CourseMiddleware
{

    public function __construct($app, $course)
    {
        $app['template']->assign('course', $course);
    }
}
