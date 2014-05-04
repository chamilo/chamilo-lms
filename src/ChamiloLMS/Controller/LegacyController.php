<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use \ChamiloSession as Session;

/**
 * Class LegacyController
 * Manages the chamilo pages starting with Display::display_header and $tpl = new Template();
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class LegacyController extends CommonController
{
    public $section;

    /**
    * Handles default Chamilo scripts handled by Display::display_header() and display_footer()
    *
    * @param \Silex\Application $app
    * @param string $file
    *
    * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|void
    */
    public function classicAction(Application $app, $file)
    {
        $responseHeaders = array();
        /** @var Request $request */
        $request = $app['request'];

        // get.
        $_GET = $request->query->all();
        // post.
        $_POST = $request->request->all();
        // echo $request->getMethod();

        //$_REQUEST = $request->request->all();
        $mainPath = $app['paths']['sys_root'].'main/';

        $fileToLoad = $mainPath.$file;

        if (is_file($fileToLoad) &&
            \Security::check_abs_path($fileToLoad, $mainPath)
        ) {

            // Default values
            $_course = api_get_course_info();
            $_user = api_get_user_info();
            $charset = 'UTF-8';
            $debug = $app['debug'];
            $text_dir = api_get_text_direction();
            $is_platformAdmin = api_is_platform_admin();
            $_cid = api_get_course_id();

            // Loading file
            ob_start();
            require_once $mainPath.$file;
            $out = ob_get_contents();
            ob_end_clean();

            // No browser cache when executing an exercise.
            if ($file == 'exercice/exercise_submit.php') {
                $responseHeaders = array(
                    'cache-control' => 'no-store, no-cache, must-revalidate'
                );
            }

            // Setting page header/footer conditions (important for LPs)
            $app['template']->setFooter($app['template.show_footer']);
            $app['template']->setHeader($app['template.show_header']);

            if (isset($htmlHeadXtra)) {
                $app['template']->addResource($htmlHeadXtra, 'string');
            }

            if (isset($interbreadcrumb)) {
                $app['template']->setBreadcrumb($interbreadcrumb);
                $app['template']->loadBreadcrumbToTemplate();
            }

            $app['template']->parseResources();

            if (isset($tpl)) {
                $response = $app['twig']->render($app['default_layout']);
            } else {
                $app['template']->assign('content', $out);
                $response = $app['twig']->render($app['default_layout']);
            }
        } else {
            return $app->abort(404, 'File not found');
        }
        return new Response($response, 200, $responseHeaders);
    }

}
