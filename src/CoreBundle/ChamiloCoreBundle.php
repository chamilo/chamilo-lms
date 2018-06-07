<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ChamiloCoreBundle.
 *
 * @package Chamilo\CoreBundle
 */
class ChamiloCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }
}
