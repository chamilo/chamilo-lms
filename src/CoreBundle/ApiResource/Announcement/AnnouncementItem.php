<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Announcement;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Announcement\AnnouncementItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/announcement/{id}',
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'id',
                        in: 'path',
                        description: 'Announcement id',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
            name: 'get_announcement_item',
            provider: AnnouncementItemProvider::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
            ],
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

    public function getId(): int
    {
        return $this->id;
    }
}
