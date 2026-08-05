<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

class OptionalCourseLinkFilter extends CidFilter
{
    public function isRequired(): bool
    {
        return false;
    }
}
