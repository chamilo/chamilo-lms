<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RedirectingController
 * Redirects /url/ to /url.
 *
 * @package Chamilo\CoreBundle\Controller
 */
class RedirectingController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|void
     */
    public function removeTrailingSlashAction(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        if ($pathInfo == '/_trans/') {
            return;
        }
        $requestUri = $request->getRequestUri();

        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        return $this->redirect($url, 301);
    }
}
