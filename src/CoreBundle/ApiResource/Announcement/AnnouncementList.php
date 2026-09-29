<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Announcement;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Announcement\AnnouncementListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/announcement/list',
            openapi: new Operation(
            ),
            name: 'get_announcement_list',
            provider: AnnouncementListProvider::class,
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
        'groups' => ['announcement_list:read'],
    ],
)]
final class AnnouncementList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['announcement_list:read'])]
    public string $id = 'announcement_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['announcement_list:read'])]
    public array $items = [];

    /**
     * @var array<int, array{id: int, label: string, username: string}>
     */
    #[Groups(['announcement_list:read'])]
    public array $authors = [];

    #[Groups(['announcement_list:read'])]
    public int $totalItems = 0;

    #[Groups(['announcement_list:read'])]
    public int $courseId = 0;

    #[Groups(['announcement_list:read'])]
    public ?int $sessionId = null;

    #[Groups(['announcement_list:read'])]
    public ?int $groupId = null;

    #[Groups(['announcement_list:read'])]
    public bool $canManage = false;

    #[Groups(['announcement_list:read'])]
    public bool $studentView = false;

    #[Groups(['announcement_list:read'])]
    public bool $canDeleteAll = false;

    #[Groups(['announcement_list:read'])]

    public function getId(): string
    {
        return $this->id;
    }
}
