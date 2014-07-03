<?php
/* For licensing terms, see /license.txt */

namespace Application\Sonata\TimelineBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class ApplicationSonataTimelineBundle
 * @package Application\Sonata\TimelineBundle
 */
class ApplicationSonataTimelineBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'SonataTimelineBundle';
    }
}
