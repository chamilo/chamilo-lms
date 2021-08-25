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
     * @var string[]
     */
    private $names = [];

    /**
     * MenuRegistry constructor.
     *
     * @param string[] $menus
     *
     * NEXT_MAJOR: remove constructor parameter
     */
    public function __construct($menus = null)
    {
        if (null !== $menus) {
            $this->names = $menus;

            @trigger_error(
                'The menus parameter in '.__CLASS__.' is deprecated since 3.3 and will be removed in 4.0.',
                E_USER_DEPRECATED
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add($menu)
    {
        if ($menu instanceof MenuBuilderInterface) {
            @trigger_error(
                'Adding a '.MenuBuilderInterface::class.' is deprecated since 3.9 and will be removed in 4.0.',
                E_USER_DEPRECATED
            );

            return;
        }

        $this->names[$menu] = $menu;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasNames()
    {
        return $this->names;
    }
}
