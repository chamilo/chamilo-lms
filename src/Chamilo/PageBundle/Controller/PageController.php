<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle\Controller;

use Chamilo\PageBundle\Entity\Page;
use Chamilo\PageBundle\Entity\Snapshot;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
    public function getLatestPages($number, Request $request)
    {
        return $this->getBlocks('news', $number, $request);
    }

    /**
     * @Route("/cms/page/blocks/{number}")
     *
     * @param int $number
     */
    public function getLatestBlocks($number, Request $request)
    {
        return $this->getBlocks('block', $number, $request);
    }

    /**
     * @Route("/cms/page/courses/{number}")
     *
     * @param int $number
     */
    public function getLatestCourses($number, Request $request)
    {
        return $this->getBlocks('course', $number, $request);
    }

    /**
     * @param string  $type
     * @param int     $number
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getBlocks($type, $number, Request $request)
    {
        switch ($type) {
            case 'block':
                $template = '@ChamiloPage/blocks.html.twig';
                break;
            case 'news':
                $template = '@ChamiloPage/latest.html.twig';
                break;
            case 'course':
                $template = '@ChamiloPage/course.html.twig';
                break;
        }

        $locale = $request->get('_locale');
        $site = $this->container->get('sonata.page.manager.site')->findOneBy(['locale' => $locale]);

        $criteria = [
            'enabled' => 1,
            'site' => $site,
            'decorate' => 1,
            'routeName' => 'page_slug',
            'metaKeyword' => $type,
        ];

        $order = ['position' => 'asc'];

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
            $template,
            ['pages' => $pagesToShow]
        );
    }
}
