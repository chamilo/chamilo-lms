<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Controller;

use Chamilo\PageBundle\Entity\Page;
use Chamilo\PageBundle\Entity\Snapshot;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class PageController.
 *
 * @package Chamilo\PageBundle\Controller
 */
class PageController extends Controller
{
    /**
     * @Route("/cms/page/latest/{number}")
     *
     * @param int $number
     */
    public function getLatestPages($number)
    {
        $site = $this->container->get('sonata.page.site.selector')->retrieve();

        $criteria = ['enabled' => 1, 'site' => $site, 'decorate' => 1, 'routeName' => 'page_slug'];
        $order = ['createdAt' => 'desc'];
        // Get latest pages
        $pages = $this->container->get('sonata.page.manager.page')->findBy($criteria, $order, $number);
        $pagesToShow = [];
        /** @var Page $page */
        foreach ($pages as $page) {
            // Skip homepage
            if ($page->getUrl() === '/') {
                continue;
            }
            $criteria = ['pageId' => $page];
            /** @var Snapshot $snapshot */
            // Check if page has a valid snapshot
            $snapshot = $this->container->get('sonata.page.manager.snapshot')->findEnableSnapshot($criteria);
            if ($snapshot) {
                $pagesToShow[] = $page;
            }
        }

        return $this->render(
            '@ChamiloPage/latest.html.twig',
            ['pages' => $pagesToShow]
        );
    }
}
