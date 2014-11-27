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

use Sylius\Component\Attribute\Model\AttributeSubjectInterface;
use Sylius\Component\Resource\Model\SoftDeletableInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Variation\Model\VariableInterface;

/**
 * Basic product interface.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
interface UserInterface extends
    AttributeSubjectInterface,
    VariableInterface,
    SoftDeletableInterface,
    TimestampableInterface
{
    /**
     * Get permalink/slug.
     *
     * @return string
     */
    public function getSlug();

    /**
     * Set the permalink.
     *
     * @param string $slug
     */
    public function setSlug($slug);


}
