<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use ChamiloLMS\Component\Editor\Connector;

/**
 * @package ChamiloLMS.Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class EditorController
{
    /**
     * @param Application $app
     * @return Response
     */
    public function filemanagerAction(Application $app)
    {
        $response = $app['template']->render_template($app['html_editor']->getTemplate());

        return new Response($response, 200, array());
    }

    /**
     *
     */
    public function connectorAction()
    {
        $chamiloConnector = new Connector();
        $opts = $chamiloConnector->getOperations();

        error_reporting(0);

        include_once api_get_path(LIBRARY_PATH).'elfinder/php/elFinderConnector.class.php';
        include_once api_get_path(LIBRARY_PATH).'elfinder/php/elFinder.class.php';
        include_once api_get_path(LIBRARY_PATH).'elfinder/php/elFinderVolumeDriver.class.php';
        include_once api_get_path(LIBRARY_PATH).'elfinder/php/elFinderVolumeLocalFileSystem.class.php';

        // Run elFinder
        $connector = new \elFinderConnector(new \elFinder($opts));
        $connector->run();
    }
}
