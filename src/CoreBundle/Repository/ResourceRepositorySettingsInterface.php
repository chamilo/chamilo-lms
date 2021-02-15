<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Component\Resource\Settings;

interface ResourceRepositorySettingsInterface
{
    public function getResourceSettings(): Settings;
}
