<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

/**
 * @author Christian Gripp <mail@core23.de>
 *
 * @deprecated since sonata-project/block-bundle 3.9, to be removed with 4.0.
 */
interface MenuBuilderInterface
{
    /**
     * Create a knp menu.
     *
     * @return ItemInterface
     */
    public function buildMenu(FactoryInterface $factory, array $options);

    /**
     * Return the name.
     *
     * @return string
     */
    public function getName();
}
