<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Controller\ToolBaseController;
use Display;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LegacyController
 * Manages the chamilo pages starting with Display::display_header and $tpl = new Template();.
 *
 * @package Chamilo\CoreBundle\Controller
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
class LegacyController extends ToolBaseController
{
    public $section;

    /**
     * Handles all request in old legacy files inside 'main/' folder.
     *
     * @param string  $name
     * @param Request $request
     * @param string  $folder
     *
     * @return Response
     */
    public function classicAction($name, Request $request, $folder = 'main')
    {
        // get.
        $_GET = $request->query->all();
        // post.
        $_POST = $request->request->all();
        $rootDir = $this->get('kernel')->getRealRootDir();
        $mainPath = $rootDir.$folder.'/';
        $fileToLoad = $mainPath.$name;

        // Setting legacy values inside the container
        $this->setContainerValuesToLegacy($request);

        if (is_file($fileToLoad) &&
            \Security::check_abs_path($fileToLoad, $mainPath)
        ) {
            /**
             * Some legacy Chamilo files still use this variables directly,
             * instead of using a function.
             */
            $is_allowed_in_course = api_is_allowed_in_course();
            $is_courseAdmin = api_is_course_admin();
            $is_platformAdmin = api_is_platform_admin();
            $toolNameFromFile = basename(dirname($fileToLoad));
            $charset = 'UTF-8';
            // Default values
            $_course = api_get_course_info();
            $_user = api_get_user_info();
            $_cid = api_get_course_id();
            $debug = $this->container->get('kernel')->getEnvironment() == 'dev' ? true : false;

            // Loading legacy file
            ob_start();
            require_once $fileToLoad;
            $out = ob_get_contents();
            ob_end_clean();

            // No browser cache when executing an exercise.
            if ($name == 'exercise/exercise_submit.php') {
                $responseHeaders = [
                    'cache-control' => 'no-store, no-cache, must-revalidate',
                ];
            }

            // Loading code to be added
            $js = isset($htmlHeadXtra) ? $htmlHeadXtra : [];

            // Loading legacy breadcrumb $interbreadcrumb
            $interbreadcrumb = isset($interbreadcrumb) ? $interbreadcrumb : null;

            // We change the layout based in this variable
            // This could be changed on the fly by a legacy script.
            $template = Container::$legacyTemplate;
            $params = [
                'legacy_breadcrumb' => $interbreadcrumb,
                'js' => $js,
            ];

            // This means the page comes from legacy use Display::display_header
            if (!empty($out)) {
                $params['content'] = $out;
            } else {
                // This means the page comes from legacy use of $tpl = new Template();
                $legacyParams = \Template::$params;
                if (!empty($legacyParams)) {
                    $params = array_merge($legacyParams, $params);
                }
            }

            // Render using Symfony2 layouts see folder:
            // src/Chamilo/ThemeBundle/Resources/views/Layout
            return $this->render(
                $template,
                $params
            );
        } else {
            // Found does not exist
            throw new NotFoundHttpException();
        }
    }

    public function pluginAction($name, Request $request)
    {
        return $this->classicAction($name, $request, 'plugin');
    }

    private function setContainerValuesToLegacy($request)
    {
        /** @var Connection $dbConnection */
        $dbConnection = $this->container->get('database_connection');
        $em = $this->get('kernel')->getContainer()->get('doctrine.orm.entity_manager');

        $database = new \Database($dbConnection, []);

        $database->setConnection($dbConnection);
        $database->setManager($em);
        Container::$container = $this->container;
        Container::setRequest($request);
        Container::$dataDir = $this->container->get('kernel')->getDataDir();
        Container::$courseDir = $this->container->get('kernel')->getDataDir();
        $this->container->get('twig')->addGlobal('api_get_cidreq', api_get_cidreq());
    }
}
