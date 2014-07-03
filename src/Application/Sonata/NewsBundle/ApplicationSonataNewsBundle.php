<?php
/* For licensing terms, see /license.txt */

namespace Application\Sonata\NewsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ApplicationSonataNewsBundle
 * @package Application\Sonata\NewsBundle
 */
class ApplicationSonataNewsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataNewsBundle';
    }
}
