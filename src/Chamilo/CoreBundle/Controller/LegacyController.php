<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Chamilo\CoreBundle\Framework\Container;
use Display;

/**
 * Class LegacyController
 * Manages the chamilo pages starting with Display::display_header and $tpl = new Template();
 * @package Chamilo\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class LegacyController extends BaseController
{
    public $section;

    /**
     * @param $name
     * @param Request $request
     * @return Response
     */
    public function classicAction($name, Request $request)
    {
        // get.
        $_GET = $request->query->all();
        // post.
        $_POST = $request->request->all();

        $rootDir = $this->get('kernel')->getRealRootDir();

        //$_REQUEST = $request->request->all();
        $mainPath = $rootDir.'main/';
        $fileToLoad = $mainPath.$name;

        // Legacy inclusions
        Container::setSession($request->getSession());

        $dbConnection = $this->container->get('database_connection');
        $database  = new \Database($dbConnection, array());
        Container::$urlGenerator = $this->container->get('router');
        Container::$security = $this->container->get('security.context');
        Container::$translator = $this->container->get('translator');
        Container::$assets = $this->container->get('templating.helper.assets');
        Container::$rootDir = $this->container->get('kernel')->getRealRootDir();
        Container::$logDir = $this->container->get('kernel')->getLogDir();
        Container::$dataDir = $this->container->get('kernel')->getDataDir();
        Container::$tempDir = $this->container->get('kernel')->getCacheDir();
        Container::$courseDir = $this->container->get('kernel')->getDataDir();
        //Container::$configDir = $this->container->get('kernel')->getConfigDir();
        Container::$htmlEditor = $this->container->get('chamilo_core.html_editor');
        Container::$twig = $this->container->get('twig');

        if (is_file($fileToLoad) &&
            \Security::check_abs_path($fileToLoad, $mainPath)
        ) {
            $toolNameFromFile = basename(dirname($fileToLoad));
            $charset = 'UTF-8';
            // Default values
            $_course = api_get_course_info();
            $_user = api_get_user_info();

            /*
            $text_dir = api_get_text_direction();
            $is_platformAdmin = api_is_platform_admin();
            $_cid = api_get_course_id();*/
            $debug = $this->container->get('kernel')->getEnvironment() == 'dev' ? true : false;

            // Loading file
            ob_start();
            require_once $fileToLoad;
            $out = ob_get_contents();
            ob_end_clean();

            // No browser cache when executing an exercise.
            if ($name == 'exercice/exercise_submit.php') {
                $responseHeaders = array(
                    'cache-control' => 'no-store, no-cache, must-revalidate'
                );
            }
            $js = isset($htmlHeadXtra) ? $htmlHeadXtra : array();

            // $interbreadcrumb is loaded in the require_once file.
            $interbreadcrumb = isset($interbreadcrumb) ? $interbreadcrumb : null;
            //$this->getTemplate()->setBreadcrumb($interbreadcrumb);
            //$breadCrumb = $this->getTemplate()->getBreadCrumbLegacyArray();
            //$menu = $this->parseLegacyBreadCrumb($breadCrumb);
            //$this->getTemplate()->assign('new_breadcrumb', $menu);
            //$this->getTemplate()->parseResources();

            /*if (isset($tpl)) {
                $response = $app['twig']->render($app['default_layout']);
            } else {
                $this->getTemplate()->assign('content', $out);
                $response = $app['twig']->render($app['default_layout']);
            }*/

            return $this->render(
                'ChamiloCoreBundle:Legacy:index.html.twig',
                array(
                    'content' => $out,
                    'js' => $js
                )
            );
        } else {
            throw new NotFoundHttpException();
        }
    }
}
