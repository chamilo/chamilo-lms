<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

interface ResourceWithAccessUrlInterface
{
    public function addUrl(AccessUrl $url);
}
