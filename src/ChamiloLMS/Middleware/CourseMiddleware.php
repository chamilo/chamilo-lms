<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Middleware;

/**
 * Class CourseMiddleware
 * @package ChamiloLMS\Middleware
 */
class CourseMiddleware
{

    public function __construct($app, $course)
    {
        $app['template']->assign('course', $course);
    }
}
