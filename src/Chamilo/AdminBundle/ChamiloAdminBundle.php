<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\AdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ApplicationSonataAdminBundle.
 *
 * @package Application\Sonata\AdminBundle
 */
class ChamiloAdminBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataAdminBundle';
    }
}
