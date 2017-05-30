<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Menu;

/**
 * @author Christian Gripp <mail@core23.de>
 */
final class MenuRegistry implements MenuRegistryInterface
{
    /**
     * @var MenuBuilderInterface[]
     */
    private $menus = array();

    /**
     * @var string[]
     */
    private $names = array();

    /**
     * MenuRegistry constructor.
     *
     * @param string[] $menus
     *
     * NEXT_MAJOR: remove constructor parameter
     */
    public function __construct($menus = null)
    {
        if (null != $menus) {
            $this->names = $menus;

            @trigger_error(
                'The menus parameter in '.__CLASS__.' is deprecated since 3.x and will be removed in 4.0.',
                E_USER_DEPRECATED
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(MenuBuilderInterface $menu)
    {
        $alias = $this->buildMenuAlias($menu);

        $this->menus[$alias] = $menu;
        $this->names[$alias] = $menu->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasNames()
    {
        return $this->names;
    }

    /**
     * Returns the menu method name.
     *
     * @param MenuBuilderInterface $menu
     *
     * @return string
     */
    private function buildMenuAlias(MenuBuilderInterface $menu)
    {
        $reflector = new \ReflectionClass($menu);
        $namespace = $reflector->getNamespaceName();
        $class = $reflector->getName();

        $bundle = str_replace('\\', '', preg_replace('/(.*Bundle)\\\\.*/i', '$1', $namespace));

        return $bundle.':'.$class.':buildMenu';
    }
}
