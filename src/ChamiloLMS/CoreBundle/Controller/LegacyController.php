<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use \ChamiloSession as Session;
use Display;

/**
 * Class LegacyController
 * Manages the chamilo pages starting with Display::display_header and $tpl = new Template();
 * @package ChamiloLMS\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class LegacyController extends BaseController
{
    public $section;

    /**
     * @param $name
     * @return Response
     */
    public function classicAction($name)
    {
        $responseHeaders = array();
        $request = $this->getRequest();

        // get.
        $_GET = $request->query->all();
        // post.
        $_POST = $request->request->all();
        // echo $request->getMethod();

        $rootDir = $this->get('kernel')->getRealRootDir();

        //$_REQUEST = $request->request->all();
        $mainPath = $rootDir.'main/';
        $fileToLoad = $mainPath.$name;

        // Legacy inclusions
        Session::setSession($this->getRequest()->getSession());
        $dbConnection = $this->container->get('database_connection');
        $database  = new \Database($dbConnection, array());
        Session::$urlGenerator = $this->container->get('router');
        Session::$security = $this->container->get('security.context');
        Session::$translator = $this->container->get('translator');
        Session::$assets = $this->container->get('templating.helper.assets');
        Session::$rootDir = $this->container->get('kernel')->getRealRootDir();
        Session::$logDir = $this->container->get('kernel')->getLogDir();
        Session::$dataDir = $this->container->get('kernel')->getDataDir();
        Session::$tempDir = $this->container->get('kernel')->getCacheDir();
        Session::$courseDir = $this->container->get('kernel')->getDataDir();
        //Session::$configDir = $this->container->get('kernel')->getConfigDir();
        Session::$htmlEditor = $this->container->get('html_editor');
        Session::$twig = $this->container->get('twig');

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

            // Setting page header/footer conditions (important for LPs)
            //$this->getTemplate()->setFooter($app['template.show_footer']);
            //$this->getTemplate()->setHeader($app['template.show_header']);

            if (isset($htmlHeadXtra)) {
                //$this->getTemplate()->addResource($htmlHeadXtra, 'string');
            }
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
                'ChamiloLMSCoreBundle:Legacy:index.html.twig',
                array('content' => $out)
            );
        } else {
            throw new NotFoundHttpException();
        }
    }
}
