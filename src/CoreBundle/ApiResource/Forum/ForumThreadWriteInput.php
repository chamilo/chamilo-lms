<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Forum;

/**
 * Input DTO used only to let API Platform resolve custom forum thread write operations.
 * The actual validation and persistence remain in the forum state processors.
 */
final class ForumThreadWriteInput
{
    public ?int $forumId = null;

    public ?string $title = null;

    public ?string $text = null;

    public ?bool $threadSticky = null;

    public ?bool $postNotification = null;

    public ?bool $gradebookEnabled = null;

    public ?int $gradebookCategoryId = null;

    public ?float $threadQualifyMax = null;

    public ?float $threadWeight = null;

    public ?string $threadTitleQualify = null;

    public ?bool $threadPeerQualify = null;

    /**
     * @var array<int, mixed>|null
     */
    public ?array $attachments = null;

    public ?string $csrfToken = null;
}
