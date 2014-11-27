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

use Sylius\Component\Variation\Model\VariantInterface as BaseVariantInterface;

/**
 * Product variant interface.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
interface VariantInterface extends BaseVariantInterface
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
     * @param null|UserInterface $product
     */
    public function setProduct(UserInterface $product = null);

    /**
     * Check whether the product is available.
     */
    public function isAvailable();

    /**
     * Return available on.
     *
     * @return \DateTime
     */
    public function getAvailableOn();

    /**
     * Set available on.
     *
     * @param null|\DateTime $availableOn
     */
    public function setAvailableOn(\DateTime $availableOn = null);
}
