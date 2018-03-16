<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ChamiloPageBundle.
 *
 * @package Chamilo\PageBundle
 */
class ChamiloPageBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataPageBundle';
    }
}
