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
use Symfony\Component\Routing\RouterInterface;

/**
 * Class NavBuilder
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
    public function createCategoryMenu(array $itemOptions = array(), $currentUri = null)
    {
        $factory = $this->container->get('knp_menu.factory');
        $menu = $factory->createItem('categories', $itemOptions);

        $this->buildCategoryMenu($menu, $itemOptions, $currentUri);

        return $menu;
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu        The item to fill with $routes
     * @param array                   $options     The item options
     * @param string                  $currentUri  The current URI
     */
    public function buildCategoryMenu(ItemInterface $menu, array $options = array(), $currentUri = null)
    {
        //$categories = $this->categoryManager->getCategoryTree();

        //$this->fillMenu($menu, $categories, $options, $currentUri);
        $menu->addChild('home', array('route' => 'home'));
    }

    /**
     * Top menu left
     * @param FactoryInterface $factory
     * @param array $options
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

        $menu->addChild(
            $translator->trans('Homepage'),
            array(
                'route' => 'main',
                'routeParameters' => array(
                    'name' => '../index.php',
                    'language' => $chamiloLocale
                )
            )
        )->setAttribute('class', 'item-menu menu-1 homepage');

        if ($checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild(
                $translator->trans('My courses'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => '../user_portal.php',
                        'language' => $chamiloLocale
                    ),
                )
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
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'mySpace/index.php',
                        'language' => $chamiloLocale
                    ),
                )
            )->setAttribute('class', 'item-menu menu-3 my-space');

            $menu->addChild(
                $translator->trans('Social network'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/home.php',
                        'language' => $chamiloLocale
                    ),
                )
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
                    'routeParameters' => ['_locale' => $locale]
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
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'auth/inscription.php',
                        'language' => $chamiloLocale
                    )
                )
            )->setAttribute('class', 'item-menu menu-3');

            $menu->addChild(
                $translator->trans('Demo'),
                array(
                    'uri' => $translator->trans('DemoMenuLink')
                )
            )->setAttribute('class', 'item-menu menu-4');

            $active = $pathInfo === 'contact' ? 'active' : '';
            $menu->addChild(
                $translator->trans('Contact'),
                array(
                    'route' => 'contact',
                    'routeParameters' => ['_locale' => $locale]
                )
            )->setAttribute('class', 'item-menu menu-5 '.$active);
        }

        return $menu;


        // Getting site information

        $site = $this->container->get('sonata.page.site.selector');
        $host = $site->getRequestContext()->getHost();
        $siteManager = $this->container->get('sonata.page.manager.site');
        /** @var Site $site */
        $site = $siteManager->findOneBy(array(
            'host'    => array($host, 'localhost'),
            'enabled' => true,
        ));

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
                        array(
                            'route' => $page->getRouteName(),
                            'routeParameters' => array(
                                'path' => $child->getUrl(),
                            ),
                        )
                    )->setAttribute('divider_append', true);
                }
            }
        }


        return $menu;
    }

    /**
     * Top menu right
     * @param FactoryInterface $factory
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function rightMenu(FactoryInterface $factory, array $options)
    {
        $checker = $this->container->get('security.authorization_checker');

        $translator = $this->container->get('translator');
        $menu = $factory->createItem('root');

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
                    'extras' => array('safe_label' => true)
                ]
            )->setAttribute('dropdown', true);

            if ($checker->isGranted('ROLE_ADMIN')) {
                $dropdown->addChild(
                    $translator->trans('Administration'),
                    array(
                        'route' => 'main',
                        'routeParameters' => array(
                            'name' => 'admin/',
                        ),
                        'icon' => ' fa fa-cog'
                    )
                );
            }

            $dropdown->addChild(
                $translator->trans('Personal agenda'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'calendar/agenda_js.php',
                    ),
                    'icon' => ' fa fa-calendar'
                )
            );

            $dropdown->addChild(
                '',
                array(
                    'divider' => true
                )
            );

            $dropdown->addChild(
                $translator->trans('Profile'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/home.php'
                    ),
                    'icon' => ' fa fa-user'
                )
            )->setAttribute('divider_append', true);

            $dropdown->addChild(
                $translator->trans('Inbox'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'messages/inbox.php',
                    ),
                    'icon' => ' fa fa-envelope'
                )
            )->setAttribute('divider_append', true);

           $dropdown->addChild(
                $translator->trans('My certificates'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'gradebook/my_certificates.php',
                    ),
                    'icon' => ' fa fa-graduation-cap'
                )
            )->setAttribute('divider_append', true);

            // legacy logout
            $dropdown->addChild(
                $translator->trans('Logout'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => '../index.php',
                        'logout' => 'logout',
                        'uid' => $user->getId(),
                    ),
                    'query' => '1',
                    'icon' => ' fa fa-sign-out'
                )
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
