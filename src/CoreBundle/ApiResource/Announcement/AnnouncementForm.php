<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Announcement;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Announcement\AnnouncementFormProcessor;
use Chamilo\CoreBundle\State\Announcement\AnnouncementFormProvider;
use DateTime;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AnnouncementForm',
    operations: [
        new Get(
            uriTemplate: '/announcement/form',
            openapi: new Operation(
                summary: 'Announcement create or edit form data',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'remind_inactive', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'remindallinactives', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'since', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_announcement_form',
            provider: AnnouncementFormProvider::class,
        ),
        new Post(
            uriTemplate: '/announcement/preview',
            openapi: new Operation(
                summary: 'Preview announcement recipients',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_announcement_preview',
            processor: AnnouncementFormProcessor::class,
        ),
        new Post(
            uriTemplate: '/announcement',
            openapi: new Operation(
                summary: 'Create an announcement',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_announcement',
            processor: AnnouncementFormProcessor::class,
        ),
        new Put(
            uriTemplate: '/announcement/{id}',
            requirements: ['id' => '\\d+'],
            openapi: new Operation(
                summary: 'Update an announcement',
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
            name: 'put_announcement',
            processor: AnnouncementFormProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['announcement_form:read']],
    denormalizationContext: ['groups' => ['announcement_form:write']],
)]
final class AnnouncementForm
{
    #[ApiProperty(identifier: true)]
    #[Groups(['announcement_form:read'])]
    public ?int $id = null;

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public string $title = '';

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public string $content = '';

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public string $language = '';

    /**
     * @var array<int, string>
     */
    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public array $recipients = [];

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public string $csrfToken = '';

    #[Groups(['announcement_form:read'])]
    public bool $canEdit = false;

    #[Groups(['announcement_form:read'])]
    public bool $isNew = true;

    #[Groups(['announcement_form:read'])]
    public bool $groupContext = false;

    #[Groups(['announcement_form:read'])]
    public string $classLabel = '';

    /**
     * @var array<int, array{value: string, label: string, type: string}>
     */
    #[Groups(['announcement_form:read'])]
    public array $recipientOptions = [];

    /**
     * @var array<int, array{id: int, label: string, recipientValues: array<int, string>}>
     */
    #[Groups(['announcement_form:read'])]
    public array $classes = [];

    /**
     * @var array<int, array{value: string, label: string}>
     */
    #[Groups(['announcement_form:read'])]
    public array $languages = [];

    /**
     * @var array<int, string>
     */
    #[Groups(['announcement_form:read'])]
    public array $tags = [];

    /**
     * @var array<int, string>
     */
    #[Groups(['announcement_form:read'])]
    public array $previewRecipients = [];

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public bool $sendByEmail = false;

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public bool $sendToUsersInSessions = false;

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public bool $sendToHrmUsers = false;

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public bool $sendCopyToSelf = true;

    #[Groups(['announcement_form:read'])]
    public bool $emailAlreadySent = false;

    #[Groups(['announcement_form:read'])]
    public bool $sendToSessionsAvailable = false;

    #[Groups(['announcement_form:read'])]
    public bool $sendToHrmAvailable = false;

    #[Groups(['announcement_form:read'])]
    public string $emailCsrfToken = '';

    #[Groups(['announcement_form:read'])]
    public bool $scheduleAvailable = false;

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public bool $scheduleByDate = false;

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public string $scheduleDate = '';

    #[Groups(['announcement_form:read'])]
    public string $scheduleMinimumDate = '';

    #[Groups(['announcement_form:read'])]
    public bool $calendarAvailable = false;

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public bool $addToCalendar = false;

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public ?DateTime $eventStartDate = null;

    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public ?DateTime $eventEndDate = null;

    /**
     * @var array<int, array{count: int, period: string}>
     */
    #[Groups(['announcement_form:read', 'announcement_form:write'])]
    public array $reminders = [];

    #[Groups(['announcement_form:read'])]
    public bool $attachmentsEnabled = false;

    #[Groups(['announcement_form:read'])]
    public string $attachmentCsrfToken = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['announcement_form:read'])]
    public array $attachments = [];

    public function getId(): ?int
    {
        return $this->id;
    }
}
