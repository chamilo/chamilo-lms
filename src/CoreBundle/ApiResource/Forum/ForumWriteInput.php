<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Forum;

/**
 * Input DTO used only to let API Platform resolve custom forum write operations.
 * The actual validation and persistence remain in the forum state processors.
 */
final class ForumWriteInput
{
    public ?string $title = null;

    public ?string $comment = null;

    public ?int $categoryId = null;

    public ?bool $moderated = null;

    public ?bool $studentsCanEdit = null;

    public ?bool $requiresApproval = null;

    public ?bool $allowAttachments = null;

    public ?bool $allowNewThreads = null;

    public ?string $defaultView = null;

    public ?bool $locked = null;

    public ?string $language = null;

    public ?int $parentResourceNodeId = null;

    public ?int $lpId = null;

    public ?int $lpParentId = null;

    public ?string $csrfToken = null;

    public ?int $groupForum = null;

    public ?string $groupVisibility = null;

    public ?string $startTime = null;

    public ?string $endTime = null;
}
