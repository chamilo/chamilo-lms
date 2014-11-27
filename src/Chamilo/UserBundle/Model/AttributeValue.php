<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chamilo\UserBundle\Model;

use Sylius\Component\Attribute\Model\AttributeValue as BaseAttributeValue;

/**
 * Product to attribute value relation.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class AttributeValue extends BaseAttributeValue implements AttributeValueInterface
{
    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return parent::getSubject();
    }

    /**
     * {@inheritdoc}
     */
    public function setUser(UserInterface $product = null)
    {
        return parent::setSubject($product);
    }
}
