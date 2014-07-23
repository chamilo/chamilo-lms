<?php

namespace ChamiloLMS\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Class Builder
 *
 * @package Sonata\Bundle\DemoBundle\Menu
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class SimpleMenuBuilder extends ContainerAware
{
    /**
     * Creates the header menu
     *
     * @param FactoryInterface $factory
     * @param array            $options
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function mainMenu(FactoryInterface $factory, array $options)
    {
        $isFooter = array_key_exists('is_footer', $options) ? $options['is_footer'] : false;

        $menu = $factory->createItem('main');

        $child = $menu->addChild(
            'My courses',
            array(
                'route' => 'userportal',
                array("attributes" => array("id" => 'nav'))
            )
        );

        $child = $menu->addChild(
            'Agenda',
            array(
                'route' => 'userportal',
                array("attributes" => array("id" => 'nav'))
            )
        );

        $child = $menu->addChild(
            'Progress',
            array(
                'route' => 'userportal',
                array("attributes" => array("id" => 'nav'))
            )
        );

        $child = $menu->addChild(
            'Administration',
            array(
                'route' => 'sonata_admin_dashboard',
                array("attributes" => array("id" => 'nav'))
            )
        );



        /*
        $dropdownExtrasOptions = $isFooter ? array(
            'uri' => "#",
            'attributes' => array('class' => 'span2'),
            'childrenAttributes' => array('class' => 'nav'),
        ) : array(
            'uri' => "#",
            'attributes' => array('class' => 'dropdown'),
            'childrenAttributes' => array('class' => 'dropdown-menu'),
            'linkAttributes' => array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown', 'data-target' => '#'),
            'label' => 'Solutions <b class="caret caret-menu"></b>',
            'extras' => array(
                'safe_label' => true,
            )
        );
        $extras = $factory->createItem('Discover', $dropdownExtrasOptions);

        $extras->addChild('Bundles', array('route' => 'page_slug', 'routeParameters' => array('path' => '/bundles')));
        $extras->addChild('Api', array('route' => 'page_slug', 'routeParameters' => array('path' => '/api-landing')));
        $extras->addChild('Gallery', array('route' => 'sonata_media_gallery_index'));
        $extras->addChild('Media & SEO', array('route' => 'home'));

        $menu->addChild($extras);
        */

        /*$menu->addChild('Admin', array(
            'route' => 'page_slug',
            'routeParameters' => array(
                'path' => '/user'
            ),
            'id' => 'admin'
        ));*/

        //if ($isFooter) {
            /*$menu->addChild('Legal notes', array(
                'route' => 'page_slug',
                'routeParameters' => array(
                    'path' => '/legal-notes',
                ),
                'id' => 'legal'
            ));*/
        //}
        return $menu;
    }

    public function footerMenu(FactoryInterface $factory, array $options)
    {
        return $this->mainMenu($factory, array_merge($options, array('is_footer' => true)));
    }

    public function getIdentifier()
    {
        return 'simple_menu';
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return 'label';
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return 'root';
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        return true;
    }

    /**
     * @param $isActive
     *
     * @return mixed
     */
    public function setIsActive($isActive)
    {
        //$isActive
    }

    /**
     * @return mixed
     */
    public function hasChildren()
    {
        return;
    }

}
