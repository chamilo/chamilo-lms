<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ChamiloUserBundle.
 *
 * @package Chamilo\UserBundle
 */
class ChamiloUserBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataUserBundle';
    }
}
