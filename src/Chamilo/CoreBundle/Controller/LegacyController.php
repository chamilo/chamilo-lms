<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class LegacyController
 * Manages the chamilo pages starting with Display::display_header and $tpl = new Template();
 * @package Chamilo\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class LegacyController extends Controller
{
    public $section;

    /**
     * @param string $name
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

        $home = $this->generateUrl('home');
        $home = str_replace('web/app_dev.php/', '', $home);
        $home .= 'main/'.$name;

        return $this->redirect($home);
    }
}
