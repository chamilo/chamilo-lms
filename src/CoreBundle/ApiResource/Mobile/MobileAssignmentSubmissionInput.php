<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Mobile;

use Symfony\Component\Validator\Constraints as Assert;

final class MobileAssignmentSubmissionInput
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

    #[Assert\Choice(choices: ['text', 'file'])]
    public string $kind = 'text';

    #[Assert\Length(max: 100000)]
    public string $text = '';

    #[Assert\Length(max: 255)]
    public ?string $fileName = null;

    #[Assert\Length(max: 255)]
    public ?string $mimeType = null;

    #[Assert\Length(max: 8000000)]
    public ?string $base64Content = null;
}
