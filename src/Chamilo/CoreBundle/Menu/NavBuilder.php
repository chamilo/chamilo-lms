<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Menu;

use Chamilo\FaqBundle\Entity\Category;
use Chamilo\PageBundle\Entity\Page;
use Chamilo\PageBundle\Entity\Site;
use Chamilo\UserBundle\Entity\User;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Class NavBuilder.
 *
 * @package Chamilo\CoreBundle\Menu
 */
class NavBuilder extends ContainerAware
{
    /**
     * @param array  $itemOptions The options given to the created menuItem
     * @param string $currentUri  The current URI
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createCategoryMenu(array $itemOptions = [], $currentUri = null)
    {
        $factory = $this->container->get('knp_menu.factory');
        $menu = $factory->createItem('categories', $itemOptions);

        $this->buildCategoryMenu($menu, $itemOptions, $currentUri);

        return $menu;
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu       The item to fill with $routes
     * @param array                   $options    The item options
     * @param string                  $currentUri The current URI
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
     * @return \Knp\Menu\ItemInterface
     */
    public function leftMenu(FactoryInterface $factory, array $options)
    {
        $checker = $this->container->get('security.authorization_checker');
        $translator = $this->container->get('translator');
        // Locale from URL
        $locale = $this->container->get('request')->get('_locale');
        if (empty($locale)) {
            // Try locale from symfony2
            $locale = $this->container->get('request')->getLocale();
        }

        $chamiloLocale = 'french2';
        switch ($locale) {
            case 'de':
                $chamiloLocale = 'german2';
                break;
            case 'fr':
                $chamiloLocale = 'french2';
                break;
        }

        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav');
        $chamiloHost = $this->getChamiloRoot();
        $menu->addChild(
            $translator->trans('Homepage'),
            [
                /*'route' => 'main',
                'routeParameters' => array(
                    'name' => '../index.php',
                    'language' => $chamiloLocale
                )*/
                'uri' => $chamiloHost.'index.php?language='.$chamiloLocale,
            ]
        )->setAttribute('class', 'item-menu menu-1 homepage');

        if ($checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild(
                $translator->trans('My courses'),
                [
                    /*'route' => 'main',
                    'routeParameters' => array(
                        'name' => '../user_portal.php',
                        'language' => $chamiloLocale
                    ),*/
                    'uri' => $chamiloHost.'user_portal.php?language='.$chamiloLocale,
                ]
            )->setAttribute('class', 'item-menu menu-2 my-course');

            /*$menu->addChild(
                $translator->trans('Personal agenda'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'calendar/agenda_js.php',
                    ),
                )
            )->setAttribute('class', 'item-menu menu-3 agenda');*/

            $menu->addChild(
                $translator->trans('Reporting'),
                [
                    /*'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'mySpace/index.php',
                        'language' => $chamiloLocale
                    ),*/
                    'uri' => $chamiloHost.'main/mySpace/index.php?language='.$chamiloLocale,
                ]
            )->setAttribute('class', 'item-menu menu-3 my-space');

            $menu->addChild(
                $translator->trans('Social network'),
                [
                    /*'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/home.php',
                        'language' => $chamiloLocale
                    ),*/
                    'uri' => $chamiloHost.'main/social/home.php?language='.$chamiloLocale,
                ]
            )->setAttribute('class', 'item-menu menu-4 social-network ');
            if ($checker->isGranted('ROLE_ADMIN')) {
                /*$menu->addChild(
                    $translator->trans('Dashboard'),
                    array(
                        'route' => 'main',
                        'routeParameters' => array(
                            'name' => 'dashboard/index.php',
                        ),
                    )
                )->setAttribute('class', 'item-menu menu-6 dashboard');*/
            }
        }

        $categories = $this->container->get('faq.entity.category_repository')->retrieveActive();
        $pathInfo = $this->container->get('router')->getContext()->getPathInfo();
        $pathInfo = str_replace('/', '', $pathInfo);
        $active = $pathInfo === 'faq' ? 'active' : '';
        if ($categories) {
            $faq = $menu->addChild(
                'FAQ',
                [
                    'route' => 'faq_index',
                    'routeParameters' => ['_locale' => $locale],
                ]
            )->setAttribute('class', 'item-menu menu-5 '.$active);

            /** @var Category $category */
            /*foreach ($categories as $category) {
                 $faq->addChild(
                    $category->getHeadline(),
                    array(
                        'route' => 'faq',
                        'routeParameters' => array(
                            'categorySlug' => $category->getSlug(),
                            'questionSlug' => '',
                        ),
                    )
                )->setAttribute('divider_append', true);
            }*/
        }

        if (!$checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild(
                $translator->trans('Subscription'),
                [
                    /*'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'auth/inscription.php',
                        'language' => $chamiloLocale
                    )*/
                    'uri' => $chamiloHost.'main/auth/inscription.php?language='.$chamiloLocale,
                ]
            )->setAttribute('class', 'item-menu menu-3');

            $menu->addChild(
                $translator->trans('Demo'),
                [
                    'uri' => $translator->trans('DemoMenuLink'),
                ]
            )->setAttribute('class', 'item-menu menu-4');

            $active = $pathInfo === 'contact' ? 'active' : '';
            $menu->addChild(
                $translator->trans('Contact'),
                [
                    'route' => 'contact',
                    'routeParameters' => ['_locale' => $locale],
                ]
            )->setAttribute('class', 'item-menu menu-5 '.$active);
        }

        return $menu;

        // Getting site information

        $site = $this->container->get('sonata.page.site.selector');
        $host = $site->getRequestContext()->getHost();
        $siteManager = $this->container->get('sonata.page.manager.site');
        /** @var Site $site */
        $site = $siteManager->findOneBy([
            'host' => [$host, 'localhost'],
            'enabled' => true,
        ]);

        if ($site) {
            $pageManager = $this->container->get('sonata.page.manager.page');

            // Parents only of homepage
            $criteria = ['site' => $site, 'enabled' => true, 'parent' => 1];
            $pages = $pageManager->findBy($criteria);
            /** @var Page $page */
            foreach ($pages as $page) {
                // Avoid home
                if ($page->getUrl() === '/') {
                    continue;
                }

                if (!$page->isCms()) {
                    continue;
                }

                $subMenu = $menu->addChild(
                    $page->getName(),
                    [
                        'route' => $page->getRouteName(),
                        'routeParameters' => [
                            'path' => $page->getUrl(),
                        ],
                    ]
                );

                /** @var Page $child */
                foreach ($page->getChildren() as $child) {
                    $subMenu->addChild(
                        $child->getName(),
                        [
                            'route' => $page->getRouteName(),
                            'routeParameters' => [
                                'path' => $child->getUrl(),
                            ],
                        ]
                    )->setAttribute('divider_append', true);
                }
            }
        }

        return $menu;
    }

    /**
     * Top menu right.
     *
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function rightMenu(FactoryInterface $factory, array $options)
    {
        $checker = $this->container->get('security.authorization_checker');
        $translator = $this->container->get('translator');
        $menu = $factory->createItem('root');
        $chamiloHost = $this->getChamiloRoot();
        $chamiloLocale = $this->getChamiloLocale();

        if ($checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $token = $this->container->get('security.token_storage');
            /** @var User $user */
            $user = $token->getToken()->getUser();
            $menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right');
            $user = $this->container->get('doctrine')->getRepository('ChamiloUserBundle:User')->find($user->getId());
            $uri = $user->getPictureUri();

            if (empty($uri)) {
                $uri = $this->container->get('templating.helper.assets')->getUrl('../../main/img/icons/32/unknown.png');
                $uri = str_replace('web/', '', $uri);
                $image = '<img src="'.$uri.'" class="img-circle"/>';
            } else {
                $uri = 'app/upload/'.$user->getPictureLegacy();
                $uri = $this->container->get('templating.helper.assets')->getUrl($uri);
                $uri = str_replace('web/app/', 'app/', $uri);
                $image = '<img src="'.$uri.'" class="img-circle"/>';
            }

            $dropdown = $menu->addChild(
                $image.'',
                [
                    'extras' => ['safe_label' => true],
                ]
            )->setAttribute('dropdown', true);

            if ($checker->isGranted('ROLE_ADMIN')) {
                $dropdown->addChild(
                    $translator->trans('Administration'),
                    [
                        /*'route' => 'main',
                        'routeParameters' => array(
                            'name' => 'admin/',
                        ),*/
                        'uri' => $chamiloHost.'main/admin/index.php?language='.$chamiloLocale,
                        'icon' => ' fa fa-cog',
                    ]
                );
            }

            $dropdown->addChild(
                $translator->trans('Personal agenda'),
                [
                    /*'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'calendar/agenda_js.php',
                    ),*/
                    'uri' => $chamiloHost.'main/calendar/agenda_js.php?language='.$chamiloLocale,
                    'icon' => ' fa fa-calendar',
                ]
            );

            $dropdown->addChild(
                '',
                [
                    'divider' => true,
                ]
            );

            $dropdown->addChild(
                $translator->trans('Profile'),
                [
                    /*'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/home.php'
                    ),*/
                    'uri' => $chamiloHost.'main/social/home.php?language='.$chamiloLocale,
                    'icon' => ' fa fa-user',
                ]
            )->setAttribute('divider_append', true);

            $dropdown->addChild(
                $translator->trans('Inbox'),
                [
                    /*'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'messages/inbox.php',
                    ),*/
                    'uri' => $chamiloHost.'main/messages/inbox.php?language='.$chamiloLocale,
                    'icon' => ' fa fa-envelope',
                ]
            )->setAttribute('divider_append', true);

            $dropdown->addChild(
                $translator->trans('My certificates'),
                [
                    /*'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'gradebook/my_certificates.php',
                    ),*/
                    'uri' => $chamiloHost.'main/gradebook/my_certificates.php?language='.$chamiloLocale,
                    'icon' => ' fa fa-graduation-cap',
                ]
            )->setAttribute('divider_append', true);

            // legacy logout
            $dropdown->addChild(
                $translator->trans('Logout'),
                [
                    /*'route' => 'main',
                    'routeParameters' => array(
                        'name' => '../index.php',
                        'logout' => 'logout',
                        'uid' => $user->getId(),
                    ),*/
                    'uri' => $chamiloHost.'index.php?logout=logout&uid='.$user->getId(),
                    'query' => '1',
                    'icon' => ' fa fa-sign-out',
                ]
            );

            /* $logoutLink
                ->setLinkAttributes(array(
                    'id' => 'logout_button',
                    //'class' => 'fa fa-power-off',
                ))
                ->setAttributes(array(
                    /*'id' => 'signin',
                    'class' => 'dropdown'
                ))
            ; */
        }

        return $menu;
    }

    /**
     * Get chamilo root.
     *
     * @return string
     */
    private function getChamiloRoot()
    {
        $urlAppend = $this->container->getParameter('url_append');
        $chamiloHost = $this->container->get('request')->getSchemeAndHttpHost();
        if (!empty($urlAppend)) {
            $chamiloHost .= '/'.$urlAppend.'/';
        } else {
            $chamiloHost .= '/';
        }

        return $chamiloHost;
    }

    /**
     * Get chamilo locale.
     *
     * @return string
     */
    private function getChamiloLocale()
    {
        // Locale from URL
        $locale = $this->container->get('request')->get('_locale');
        if (empty($locale)) {
            // Try locale from symfony2
            $locale = $this->container->get('request')->getLocale();
        }

        $chamiloLocale = 'french2';
        switch ($locale) {
            case 'de':
                $chamiloLocale = 'german2';
                break;
            case 'fr':
                $chamiloLocale = 'french2';
                break;
        }

        return $chamiloLocale;
    }

    /*public function profileMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $security = $this->container->get('security.context');
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked');

            $menu->addChild('Inbox', array('route' => 'logout'));
            $menu->addChild('Compose', array('route' => 'logout'));
            $menu->addChild('Edit', array('route' => 'logout'));
        }

        return $menu;
    }*/
}
