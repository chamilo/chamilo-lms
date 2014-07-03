<?php
/* For licensing terms, see /license.txt */

namespace Application\Sonata\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ApplicationSonataUserBundle
 * @package Application\Sonata\UserBundle
 */
class ApplicationSonataUserBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataUserBundle';
    }
}
