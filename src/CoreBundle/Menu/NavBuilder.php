<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Menu;

use Chamilo\FaqBundle\Entity\Category;
use Chamilo\PageBundle\Entity\Page;
use Chamilo\PageBundle\Entity\Site;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class NavBuilder.
 *
 * @package Chamilo\CoreBundle\Menu
 */
class NavBuilder implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param array  $itemOptions The options given to the created menuItem
     * @param string $currentUri  The current URI
     *
     * @return ItemInterface
     */
    public function createCategoryMenu(array $itemOptions = [], $currentUri = null)
    {
        $factory = $this->container->get('knp_menu.factory');
        $menu = $factory->createItem('categories', $itemOptions);

        $this->buildCategoryMenu($menu, $itemOptions, $currentUri);

        return $menu;
    }

    /**
     * @param ItemInterface $menu       The item to fill with $routes
     * @param array         $options    The item options
     * @param string        $currentUri The current URI
     */
    public function buildCategoryMenu(ItemInterface $menu, array $options = [], $currentUri = null)
    {
        //$categories = $this->categoryManager->getCategoryTree();

        //$this->fillMenu($menu, $categories, $options, $currentUri);
        $menu->addChild('home', ['route' => 'home']);
    }

    /**
     * Top menu left.
     *
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return ItemInterface
     */
    public function menuApp(FactoryInterface $factory, array $options): ItemInterface
    {
        $container = $this->container;
        $checker = $container->get('security.authorization_checker');
        $translator = $container->get('translator');

        $menu = $factory->createItem('root');

        $settingsManager = $container->get('chamilo.settings.manager');

        $menu->addChild(
            'home',
            [   'label' => $translator->trans('Home'),
                'route' => 'legacy_index',
                'icon' => 'home',
            ]
        );

        if ($checker && $checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild(
                'courses',
                [
                    'label' => $translator->trans('My courses'),
                    'route' => 'legacy_main',
                    'icon' => 'book',
                    'routeParameters' => [
                        'name' => '../user_portal.php',
                    ],
                ]
            );


            $lang = $translator->trans('CreateCourse');
            if ($settingsManager->getSetting('course.course_validation') == 'true') {
                $lang = $translator->trans('CreateCourseRequest');
            }

            $menu['courses']->addChild(
                'create-course',
                [
                    'label' => $lang,
                    'route' => 'legacy_main',
                    'routeParameters' => [
                        'name' => 'create_course/add_course.php',
                    ],
                ]
            );

            $browse = $settingsManager->getSetting('display.allow_students_to_browse_courses');

            if ($browse == 'true') {
                if ($checker->isGranted('ROLE_STUDENT') && !api_is_drh(
                    ) && !api_is_session_admin()
                ) {
                    $menu['courses']->addChild(
                        'catalog',
                        [
                            'label' => $translator->trans('CourseCatalog'),
                            'route' => 'legacy_main',
                            'routeParameters' => [
                                'name' => 'auth/courses.php'
                            ],
                        ]
                    );
                }
            }

            $menu->addChild(
                'calendar',
                [
                    'label' => $translator->trans('Calendar'),
                    'route' => 'legacy_main',
                    'icon' => 'calendar-alt',
                    'routeParameters' => [
                        'name' => 'calendar/agenda_js.php',
                    ],
                ]
            );

            $menu->addChild(
                'reports',
                [
                    'label' => $translator->trans('Reporting'),
                    'route' => 'legacy_main',
                    'icon' => 'chart-bar',
                    'routeParameters' => [
                        'name' => 'mySpace/index.php',
                    ],
                ]
            );

            if ('true' === $settingsManager->getSetting('social.allow_social_tool')) {
                $menu->addChild(
                    'social',
                    [
                        'label' => $translator->trans('Social'),
                        'route' => 'legacy_main',
                        'icon' => 'heart',
                        'routeParameters' => [
                            'name' => 'social/home.php',
                        ],
                    ]
                );
            }

            if ($checker->isGranted('ROLE_ADMIN')) {
                $menu->addChild(
                    'dashboard',
                    [
                        'label' => $translator->trans('Dashboard'),
                        'route' => 'legacy_main',
                        'icon' => 'cube',
                        'routeParameters' => [
                            'name' => 'dashboard/index.php',
                        ],
                    ]
                );
                $menu->addChild(
                    'administrator',
                    [
                        'label' => $translator->trans('Administration'),
                        'route' => 'legacy_main',
                        'icon' => 'cogs',
                        'routeParameters' => [
                            'name' => 'admin/index.php',
                        ],
                    ]
                );
            }
        }

        $categories = $container->get('doctrine')->getRepository('ChamiloFaqBundle:Category')->retrieveActive();
        if ($categories) {
            $faq = $menu->addChild(
                'FAQ',
                [
                    'route' => 'faq_index',
                ]
            );
            /** @var Category $category */
            foreach ($categories as $category) {
                $faq->addChild(
                    $category->getHeadline(),
                    [
                        'route' => 'faq',
                        'routeParameters' => [
                            'categorySlug' => $category->getSlug(),
                            'questionSlug' => '',
                        ],
                    ]
                )->setAttribute('divider_append', true);
            }
        }

        // Getting site information
        $site = $container->get('sonata.page.site.selector');
        $host = $site->getRequestContext()->getHost();
        $siteManager = $container->get('sonata.page.manager.site');
        /** @var Site $site */
        $site = $siteManager->findOneBy([
            'host' => [$host, 'localhost'],
            'enabled' => true,
        ]);

        $isLegacy = $container->get('request_stack')->getCurrentRequest()->get('load_legacy');
        $urlAppend = $container->getParameter('url_append');
        $legacyIndex = '';
        if ($isLegacy) {
            $legacyIndex = $urlAppend.'/public';
        }

        if ($site) {
            $pageManager = $container->get('sonata.page.manager.page');
            // Parents only of homepage
            $criteria = ['site' => $site, 'enabled' => true, 'parent' => 1];
            $pages = $pageManager->findBy($criteria);

            //$pages = $pageManager->loadPages($site);
            /** @var Page $page */
            foreach ($pages as $page) {
                /*if ($page->getRouteName() !== 'page_slug') {
                    continue;
                }*/

                // Avoid home
                if ($page->getUrl() === '/') {
                    continue;
                }

                if (!$page->isCms()) {
                    continue;
                }

                $url = $legacyIndex.$page->getUrl();

                $subMenu = $menu->addChild(
                    $page->getName(),
                    [
                        'route' => $page->getRouteName(),
                        'routeParameters' => [
                            'path' => $url,
                        ],
                    ]
                );

                /** @var Page $child */
                foreach ($page->getChildren() as $child) {
                    $url = $legacyIndex.$child->getUrl();
                    $subMenu->addChild(
                        $child->getName(),
                        [
                            'route' => $page->getRouteName(),
                            'routeParameters' => [
                                'path' => $url,
                            ],
                        ]
                    )->setAttribute('divider_append', true);
                }
            }
        }

        // Set CSS classes for the items
        foreach ($menu->getChildren() as $child) {
            $child
                ->setLinkAttribute('class', 'sidebar-link')
                ->setAttribute('class', 'nav-item');
        }

        return $menu;
    }
}
