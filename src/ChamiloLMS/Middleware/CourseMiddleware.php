<?php

namespace ChamiloLMS\Middleware;

/**
 * Class CourseMiddleware
 * @package ChamiloLMS\Middleware
 */
class CourseMiddleware
{

    public function __construct(Application $app, $course)
    {
        $app['template']->assign('course', $course);
    }
}
