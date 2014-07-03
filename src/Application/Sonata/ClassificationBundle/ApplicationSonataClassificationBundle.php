<?php
/* For licensing terms, see /license.txt */

namespace Application\Sonata\ClassificationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ApplicationSonataClassificationBundle
 * @package Application\Sonata\ClassificationBundle
 */
class ApplicationSonataClassificationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataClassificationBundle';
    }
}
