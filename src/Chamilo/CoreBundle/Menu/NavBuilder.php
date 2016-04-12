<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Menu;

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

            /*$menu->addChild(
                $translator->trans('MyCourses'),
                array('route' => 'userportal')
            );

            $menu->addChild(
                $translator->trans('Calendar'),
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
                $translator->trans('Social'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'social/home.php',
                    ),
                )
            );

            $menu->addChild(
                $translator->trans('Dashboard'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'dashboard/index.php',
                    ),
                )
            );

            if ($checker->isGranted('ROLE_ADMIN')) {
                $menu->addChild(
                    $translator->trans('Administration'),
                    array(
                        'route' => 'administration',
                    )
                );
            }*/
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

            $dropdown = $menu->addChild(
                $user->getUsername()
            )->setAttribute('dropdown', true);

            $dropdown->addChild(
                $translator->trans('Profile'),
                array('route' => 'fos_user_profile_show')
            )->setAttribute('divider_append', true);

            $dropdown->addChild(
                $translator->trans('Inbox'),
                array(
                    'route' => 'main',
                    'routeParameters' => array(
                        'name' => 'messages/inbox.php',
                    ),
                )
            )->setAttribute('divider_append', true);


            $logoutLink = $menu->addChild('Logout', array('route' => 'logout'));
            $logoutLink
                ->setLinkAttributes(array(
                    'id' => 'logout_button',
                    'class' => 'fa fa-power-off'
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
