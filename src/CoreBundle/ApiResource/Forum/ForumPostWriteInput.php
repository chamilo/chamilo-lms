<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Forum;

/**
 * Input DTO used only to let API Platform resolve custom forum post write operations.
 * The actual validation and persistence remain in the forum state processors.
 */
final class ForumPostWriteInput
{
    public ?int $forumId = null;

    public ?int $threadId = null;

    public ?int $parentPostId = null;

    public ?string $title = null;

    public ?string $text = null;

    public ?bool $postNotification = null;

    public ?bool $giveRevision = null;

    public ?string $revisionLanguage = null;

    /**
     * @var array<int, mixed>|null
     */
    public ?array $attachments = null;

    public ?string $csrfToken = null;
}
