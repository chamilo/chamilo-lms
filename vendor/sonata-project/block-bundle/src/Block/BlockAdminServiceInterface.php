<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block;

use Sonata\BlockBundle\Block\Service\AdminBlockServiceInterface;

@trigger_error(
    'The '.__NAMESPACE__.'BlockAdminServiceInterface interface is deprecated since 3.2 '.
    'and will be removed with the 4.0 release. '.
    'Use '.__NAMESPACE__.'\Service\AdminBlockServiceInterface instead.',
    E_USER_DEPRECATED
);

/**
 * NEXT_MAJOR: remove this interface.
 *
 * @deprecated since 3.2, to be removed with 4.0
 */
interface BlockAdminServiceInterface extends AdminBlockServiceInterface
{
}
