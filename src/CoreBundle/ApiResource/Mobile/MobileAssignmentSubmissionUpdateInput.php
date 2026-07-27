<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Mobile;

use Symfony\Component\Validator\Constraints as Assert;

final class MobileAssignmentSubmissionUpdateInput
{
    #[Assert\Positive]
    public int $assignmentId = 0;

    #[Assert\Positive]
    public int $courseId = 0;

    #[Assert\Positive]
    public ?int $sessionId = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $title = '';

    #[Assert\Length(max: 100000)]
    public string $description = '';
}
