<?php

/*
 * This file is part of the Sylius package.
 *
 * (c); Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chamilo\UserBundle\Model;

use Sylius\Component\Attribute\Model\AttributeValueInterface as BaseAttributeValueInterface;

/**
 * Product to attribute relation interface.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
interface AttributeValueInterface extends BaseAttributeValueInterface
{
    /**
     * Get product.
     *
     * @return UserInterface
     */
    public function getUser();

    /**
     * Set product.
     *
     * @param UserInterface|null $product
     */
    public function setUser(UserInterface $product = null);
}
