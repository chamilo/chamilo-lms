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

namespace Sonata\Doctrine\Bridge\Symfony\Bundle;

use Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle as ForwardCompatibleSonataDoctrineBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

@trigger_error(sprintf(
    'The %s\SonataDoctrineBundle class is deprecated since sonata-project/doctrine-extensions 1.9, to be removed in version 2.0. Use %s instead.',
    __NAMESPACE__,
    ForwardCompatibleSonataDoctrineBundle::class
), E_USER_DEPRECATED);

if (false) {
    /**
     * NEXT_MAJOR: remove this class.
     *
     * @deprecated Since sonata-project/doctrine-extensions 1.9, to be removed in 2.0. Use Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle instead.
     */
    final class SonataDoctrineBundle extends Bundle
    {
    }
}

class_alias(ForwardCompatibleSonataDoctrineBundle::class, __NAMESPACE__.'\SonataDoctrineBundle');
