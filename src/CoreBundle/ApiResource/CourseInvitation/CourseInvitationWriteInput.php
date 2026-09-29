<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseInvitation;

use Symfony\Component\Validator\Constraints as Assert;

final class CourseInvitationWriteInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';
}
