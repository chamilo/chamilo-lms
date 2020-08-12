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

namespace Sonata\BlockBundle\Test;

@trigger_error(
    'The '.__NAMESPACE__.'\AbstractBlockServiceTestCase class is deprecated since sonata-project/block-bundle 3.16 '.
    'and will be removed with the 4.0 release. '.
    'Use '.__NAMESPACE__.'\BlockServiceTestCase instead.',
    E_USER_DEPRECATED
);

/**
 * Abstract test class for block service tests.
 *
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 *
 * @deprecated since sonata-project/block-bundle 3.16, to be removed in 4.0. Use Sonata\BlockBundle\Test\BlockServiceTestCase instead.
 */
abstract class AbstractBlockServiceTestCase extends BlockServiceTestCase
{
}
