<?php
/* For licensing terms, see /license.txt */

namespace Application\Sonata\AdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ApplicationSonataAdminBundle
 * @package Application\Sonata\AdminBundle
 */
class ApplicationSonataAdminBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataAdminBundle';
    }
}
