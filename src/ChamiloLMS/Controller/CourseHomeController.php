<?php

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LearnpathController
 * @package ChamiloLMS\Controller
 */
class CourseHomeController
{
    public $language_files = array('course_home','courses');

    public function indexAction(Application $app, $courseCode)
    {
        $list = api_get_tool_urls();
        $content = null;

        foreach ($list as $tool) {
            $content .= \Display::url($tool, $tool.'?cidReq='.$courseCode);
        }

        $app['template']->assign('content', $content);

        $response = $app['template']->render_layout('layout_2_col.tpl');

        //return new Response($response, 200, array('Cache-Control' => 's-maxage=3600, public'));
        return new Response($response, 200, array());
    }
}