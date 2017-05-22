<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Menu;

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

        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav');

        $menu->addChild(
            $translator->trans('Home'),
            array('route' => 'home')
        );

        if ($checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->addChild(
                $translator->trans('My courses'),
                array('route' => 'userportal')
            );

            $menu->addChild(
                $translator->trans('Personal agenda'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'calendar/agenda_js.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('Reporting'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'mySpace/index.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('Social network'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/home.php',
                    ),
                )
            );

            if ($checker->isGranted('ROLE_ADMIN')) {
                $menu->addChild(
                    $translator->trans('Dashboard'),
                    array(
                        'route' => 'main',
                        'routeParameters' => array(
                            'name' => 'dashboard/index.php',
                        ),
                    )
                );

                $menu->addChild(
                    $translator->trans('Administration'),
                    array(
                        'route' => 'main',
                        'routeParameters' => array(
                            'name' => 'social/home.php',
                        )
                    )
                );
            }
        }

        $categories = $this->container->get('faq.entity.category_repository')->retrieveActive();
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
                    array(
                        'route' => 'faq',
                        'routeParameters' => array(
                            'categorySlug' => $category->getSlug(),
                            'questionSlug' => '',
                        ),
                    )
                )->setAttribute('divider_append', true);
            }
        }

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

        // <nav class="navbar navbar-default">
        if ($checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $token = $this->container->get('security.token_storage');
            /** @var User $user */
            $user = $token->getToken()->getUser();
            $menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right');
            $dropdown = $menu->addChild($user->getUsername())->setAttribute('dropdown', true);

            /*$dropdown->addChild(
                $translator->trans('Profile'),
                array('route' => 'fos_user_profile_show')
            )->setAttribute('divider_append', true);*/
            $dropdown->addChild(
                $translator->trans('Inbox'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'messages/inbox.php',
                    ),
                )
            )->setAttribute('divider_append', true);

            // legacy logout
            $logoutLink = $menu->addChild(
                $translator->trans('Logout'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => '../index.php',
                        'logout' => 'logout',
                        'uid' => $user->getId(),
                    ),
                    'query' => '1',
                    'icon' => 'fa fa-sign-out'
                )
            );

            $logoutLink
                ->setLinkAttributes(array(
                    'id' => 'logout_button',
                    'class' => 'fa fa-power-off',
                ))
                ->setAttributes(array(
                    /*'id' => 'signin',
                    'class' => 'dropdown'*/
                ))
            ;
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
