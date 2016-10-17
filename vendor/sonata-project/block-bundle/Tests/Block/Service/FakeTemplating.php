<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Tests\Block\Service;

use Sonata\BlockBundle\Test\FakeTemplating as BaseTemplating;

@trigger_error(
    'The '.__NAMESPACE__.'\FakeTemplating class is deprecated since version 3.1 and will be removed in 4.0.'
    .' Use Sonata\BlockBundle\Test\FakeTemplating instead.',
    E_USER_DEPRECATED
);

/**
 * @deprecated since version 3.1 and will be removed in 4.0. Use Sonata\BlockBundle\Test\FakeTemplating instead
 */
class FakeTemplating extends BaseTemplating
{
}
