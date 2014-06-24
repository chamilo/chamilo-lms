<?php
/* For licensing terms, see /license.txt */

namespace Application\Sonata\MediaBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ApplicationSonataMediaBundle
 * @package Application\Sonata\MediaBundle
 */
class ApplicationSonataMediaBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataMediaBundle';
    }
}
