<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

interface ResourceWithUrlInterface
{
    public function addUrl(AccessUrl $url);
}
