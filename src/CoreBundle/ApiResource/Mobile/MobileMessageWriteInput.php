<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Mobile;

use Symfony\Component\Validator\Constraints as Assert;

final class MobileMessageWriteInput
{
    #[Assert\Positive]
    public int $recipientId = 0;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $title = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100000)]
    public string $content = '';

    #[Assert\Positive]
    public ?int $parentId = null;
}
