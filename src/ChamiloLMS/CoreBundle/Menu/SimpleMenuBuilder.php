<?php

namespace ChamiloLMS\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Avanzu\AdminThemeBundle\Model\MenuItemInterface as ThemeMenuItem;

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

        $shopCategories = $this->container->get('sonata.classification.manager.category')->findBy(array('enabled' => true, 'parent' => null));

        $menuOptions = array_merge($options, array(
            'childrenAttributes' => array('class' => 'nav nav-pills'),
        ));

        $menu = $factory->createItem('main', $menuOptions);

        //$shopMenuParams = array('route' => 'sonata_catalog_index');
        $shopMenuParams = array('route' => 'home');

        if (count($shopCategories) > 0 && !$isFooter) {
            $shopMenuParams = array_merge($shopMenuParams, array(
                'attributes' => array('class' => 'dropdown'),
                'childrenAttributes' => array('class' => 'dropdown-menu'),
                'linkAttributes' => array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown', 'data-target' => '#'),
                'label' => 'Products <b class="caret caret-menu"></b>',
                'extras' => array(
                    'safe_label' => true,
                )
            ));
        }

        if ($isFooter) {
            $shopMenuParams = array_merge($shopMenuParams, array(
                'attributes' => array('class' => 'span2'),
                "childrenAttributes" => array('class' => 'nav')
            ));
        }

        $shop = $menu->addChild('Shops', $shopMenuParams);

        $menu->addChild('News', array('route' => 'sonata_news_home'));

        foreach ($shopCategories as $category) {
            $shop->addChild($category->getName(), array(
                'route' => 'sonata_catalog_category',
                'routeParameters' => array(
                    'category_id'   => $category->getId(),
                    'category_slug' => $category->getSlug()),
                )
            );
        }

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
        //$extras->addChild('Media & SEO', array('route' => 'sonata_demo_media'));

        $menu->addChild($extras);

        $menu->addChild('Admin', array(
            'route' => 'page_slug',
            'routeParameters' => array(
                'path' => '/user'
            )
        ));

        if ($isFooter) {
            $menu->addChild('Legal notes', array(
                'route' => 'page_slug',
                'routeParameters' => array(
                    'path' => '/legal-notes',
                )
            ));
        }

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
