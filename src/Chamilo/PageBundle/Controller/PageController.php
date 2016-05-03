<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class PageController
 * @package Chamilo\PageBundle\Controller
 */
class PageController extends Controller
{
    /**
     * @Route("/cms/page/latest/{number}")
     * @param int $number
     */
    public function getLatestPages($number)
    {
        $site = $this->container->get('sonata.page.site.selector')->retrieve();

        $criteria = ['enabled' => 1, 'site' => $site, 'decorate' => 1];
        $order = ['publicationDateStart' => 'desc'];
        $order = [];
        $pages = $this->container->get('sonata.page.manager.page')->findBy($criteria, $order, $number);
        //$pages = $this->container->get('sonata.page.manager.snapshot')->findBy($criteria, $order, $number);

        //$site = $this->container->get('sonata.page.site.selector.host')->retrieve();
        return $this->render('@ChamiloPage/latest.html.twig', ['pages' => $pages]);
    }
}