<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\App\Certificate;

use Chamilo\CoreBundle\Controller\BaseController;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CertificateController
 * @package Chamilo\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class CertificateController extends BaseController
{
    /**
     * @param $id
     * @return string
     */
    public function indexAction($id)
    {
        $certificate = new \Certificate($id);

        // Show certificate HTML.
        return $certificate->show(true);
    }
}
