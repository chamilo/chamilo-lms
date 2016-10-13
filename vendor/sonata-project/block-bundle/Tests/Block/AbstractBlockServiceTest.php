<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Block;

use Sonata\BlockBundle\Test\AbstractBlockServiceTestCase;

@trigger_error(
    'The '.__NAMESPACE__.'\AbstractBlockServiceTest class is deprecated since version 3.1 and will be removed in 4.0.'
    .' Use Sonata\BlockBundle\Test\AbstractBlockServiceTestCase instead.',
    E_USER_DEPRECATED
);

/**
 * @deprecated Deprecated since version 3.1. Use Sonata\BlockBundle\Test\AbstractBlockServiceTestCase instead
 */
abstract class AbstractBlockServiceTest extends AbstractBlockServiceTestCase
{
}
