<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ClassificationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ChamiloClassificationBundle.
 *
 * @package Chamilo\ClassificationBundle
 */
class ChamiloClassificationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataClassificationBundle';
    }
}
