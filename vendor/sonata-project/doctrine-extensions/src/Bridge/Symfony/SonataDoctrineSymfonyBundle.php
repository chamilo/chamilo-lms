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

namespace Sonata\Doctrine\Bridge\Symfony;

// This file and its class alias is required in order to let Symfony Flex
// autodiscovery to find the bundle.
// The string "Symfony\Component\HttpKernel\Bundle\Bundle" must also be present.
// @see https://github.com/symfony/flex/pull/612/files.
class_alias(SonataDoctrineBundle::class, __NAMESPACE__.'\SonataDoctrineSymfonyBundle');
