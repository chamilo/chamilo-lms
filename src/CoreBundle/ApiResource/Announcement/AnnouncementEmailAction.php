<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Announcement;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Announcement\AnnouncementEmailProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AnnouncementEmailAction',
    operations: [
        new Post(
            uriTemplate: '/announcement/{id}/send-email',
            requirements: ['id' => '\\d+'],
            openapi: new Operation(
                summary: 'Send an announcement by email',
                parameters: [
                    new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_announcement_send_email',
            processor: AnnouncementEmailProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['announcement_email:read']],
    denormalizationContext: ['groups' => ['announcement_email:write']],
)]
final class AnnouncementEmailAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['announcement_email:read'])]
    public ?int $id = null;

    #[Groups(['announcement_email:write'])]
    public bool $sendByEmail = false;

    #[Groups(['announcement_email:write'])]
    public bool $sendToUsersInSessions = false;

    #[Groups(['announcement_email:write'])]
    public bool $sendToHrmUsers = false;

    #[Groups(['announcement_email:write'])]
    public bool $sendCopyToSelf = false;

    #[Groups(['announcement_email:write'])]
    public string $csrfToken = '';

    #[Groups(['announcement_email:read'])]
    public bool $success = false;

    #[Groups(['announcement_email:read'])]
    public bool $partial = false;

    #[Groups(['announcement_email:read'])]
    public bool $emailSent = false;

    #[Groups(['announcement_email:read'])]
    public bool $copySent = false;

    #[Groups(['announcement_email:read'])]
    public int $sentCount = 0;

    #[Groups(['announcement_email:read'])]
    public int $failedCount = 0;

    #[Groups(['announcement_email:read'])]
    public int $internalMessageCount = 0;

    #[Groups(['announcement_email:read'])]
    public int $internalMessageCreatedCount = 0;

    #[Groups(['announcement_email:read'])]
    public int $internalMessageFailedCount = 0;

    /**
     * @var array<int, string>
     */
    #[Groups(['announcement_email:read'])]
    public array $failedRecipients = [];

    #[Groups(['announcement_email:read'])]
    public string $message = '';
}
