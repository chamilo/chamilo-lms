<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Announcement;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Announcement\AnnouncementItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/announcement/{id}',
            requirements: ['id' => '\\d+'],
            name: 'get_announcement_item',
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'id',
                        in: 'path',
                        description: 'Announcement id',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'cid',
                        in: 'query',
                        description: 'Course id',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'sid',
                        in: 'query',
                        description: 'Session id',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'gid',
                        in: 'query',
                        description: 'Course group id',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'isStudentView',
                        in: 'query',
                        description: 'Force the read-only student view',
                        required: false,
                        schema: ['type' => 'boolean'],
                    ),
                ],
            ),
            provider: AnnouncementItemProvider::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['announcement_item:read'],
    ],
)]
final class AnnouncementItem
{
    #[ApiProperty(identifier: true)]
    #[Groups(['announcement_item:read'])]
    public int $id = 0;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['announcement_item:read'])]
    public array $item = [];

    #[Groups(['announcement_item:read'])]
    public int $courseId = 0;

    #[Groups(['announcement_item:read'])]
    public ?int $sessionId = null;

    #[Groups(['announcement_item:read'])]
    public ?int $groupId = null;

    #[Groups(['announcement_item:read'])]
    public bool $canManage = false;

    #[Groups(['announcement_item:read'])]
    public bool $canViewRecipients = false;

    #[Groups(['announcement_item:read'])]
    public bool $studentView = false;

    #[Groups(['announcement_item:read'])]
    public bool $attachmentsEnabled = false;

    #[Groups(['announcement_item:read'])]
    public string $csrfToken = '';

    #[Groups(['announcement_item:read'])]
    public string $attachmentCsrfToken = '';

    public function getId(): int
    {
        return $this->id;
    }
}
