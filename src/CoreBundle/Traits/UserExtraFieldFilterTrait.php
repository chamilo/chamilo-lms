<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use ApiPlatform\Metadata\ApiFilter;
use Chamilo\CoreBundle\Filter\UserExtraFieldFilter;

/**
 * Properties to use as filters. To search by a user extra field.
 * The API Resource must have a relation with User
 */
trait UserExtraFieldFilterTrait
{
    #[ApiFilter(UserExtraFieldFilter::class)]
    protected string $userExtraFieldName;

    #[ApiFilter(UserExtraFieldFilter::class)]
    protected string $userExtraFieldValue;
}
