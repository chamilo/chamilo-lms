<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Serializer;

use JMS\Serializer\Handler\SubscribingHandlerInterface;

/**
 * @author Sylvain Deloux <sylvain.deloux@ekino.com>
 */
interface SerializerHandlerInterface extends SubscribingHandlerInterface
{
    /**
     * @return string
     */
    public static function getType();
}
