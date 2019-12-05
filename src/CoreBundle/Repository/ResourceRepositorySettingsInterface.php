<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Component\Utils\ResourceSettings;

/**
 * Class ResourceRepositoryInterface.
 */
interface ResourceRepositorySettingsInterface
{
    public function getResourceSettings(): ResourceSettings;
}
