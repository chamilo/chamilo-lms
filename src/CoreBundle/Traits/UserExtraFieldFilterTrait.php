<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use ApiPlatform\Core\Annotation\ApiFilter;
use Chamilo\CoreBundle\Filter\UserExtraFieldFilter;

trait UserExtraFieldFilterTrait
{
    #[ApiFilter(UserExtraFieldFilter::class)]
    protected string $userExtraFieldName;

    #[ApiFilter(UserExtraFieldFilter::class)]
    protected string $userExtraFieldValue;
}
