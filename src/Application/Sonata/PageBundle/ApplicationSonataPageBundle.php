<?php
/* For licensing terms, see /license.txt */

namespace Application\Sonata\PageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ApplicationSonataPageBundle
 * @package Application\Sonata\PageBundle
 */
class ApplicationSonataPageBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataPageBundle';
    }
}
