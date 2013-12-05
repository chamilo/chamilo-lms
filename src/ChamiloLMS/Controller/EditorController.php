<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use ChamiloLMS\Component\Editor\Connector;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
//use ChamiloLMS\Controller\CommonController;


/**
 * @package ChamiloLMS.Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class EditorController extends CommonController
{
    /**
     * @Route("/filemanager")
     * @Method({"GET"})
     */
    public function fileManagerAction()
    {
        $response = $this->getTemplate()->renderTemplate($this->getHtmlEditor()->getTemplate());
        return new Response($response, 200, array());
    }

    /**
     * @Route("/templates")
     * @Method({"GET"})
     */
    public function getTemplatesAction()
    {
        $templates = $this->getManager()->getRepository('Entity\SystemTemplate')->findAll();
        $templates = $this->getHtmlEditor()->formatTemplates($templates);
        $this->getTemplate()->assign('templates', $templates);
        $response = $this->getTemplate()->renderTemplate('javascript/editor/ckeditor/templates.tpl');

        return new Response($response, 200, array());
    }

    /**
     * @Route("/connector")
     * @Method({"GET"})
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
