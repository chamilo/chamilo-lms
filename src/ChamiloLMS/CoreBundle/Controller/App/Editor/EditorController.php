<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\App\Editor;

use ChamiloLMS\CoreBundle\Controller\BaseController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use ChamiloLMS\CoreBundle\Component\Editor\Connector;
use ChamiloLMS\CoreBundle\Component\Editor\Finder;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use ChamiloLMS\CoreBundle\Component\Editor\Driver\elFinderVolumePersonalDriver;

/**
 * @package ChamiloLMS.Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class EditorController extends BaseController
{
    /**
     * Gets that rm.wav sound
     * @Route("/sounds/{file}")
     * @Method({"GET"})
     */
    public function getSoundAction($file)
    {
        $file = api_get_path(LIBRARY_PATH).'elfinder/rm.wav';
        return $this->app->sendFile($file);
    }

    /**
     * @Route("/filemanager")
     * @Method({"GET"})
     */
    public function fileManagerAction()
    {
        $this->getTemplate()->assign('course', $this->getCourse());
        $this->getTemplate()->assign('user', $this->getUser());
        $response = $this->getTemplate()->renderTemplate($this->getHtmlEditor()->getEditorTemplate());
        return new Response($response, 200, array());
    }

    /**
     * @Route("/templates")
     * @Method({"GET"})
     */
    public function getTemplatesAction()
    {
        $templates = $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\SystemTemplate')->findAll();
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
        error_reporting(-1);
        $connector = $this->getEditorConnector();
        $driverList = $this->getRequest()->get('driver_list');

        if (!empty($driverList)) {
            $connector->setDriverList(explode(',', $driverList));
        }

        $operations = $connector->getOperations();

        include_once api_get_path(LIBRARY_PATH).'elfinder/php/elFinderConnector.class.php';
        include_once api_get_path(LIBRARY_PATH).'elfinder/php/elFinder.class.php';
        include_once api_get_path(LIBRARY_PATH).'elfinder/php/elFinderVolumeDriver.class.php';
        include_once api_get_path(LIBRARY_PATH).'elfinder/php/elFinderVolumeLocalFileSystem.class.php';

        // Run elFinder
        $finder = new Finder($operations);
        $elFinderConnector = new \elFinderConnector($finder);
        $elFinderConnector->run();
    }
}
