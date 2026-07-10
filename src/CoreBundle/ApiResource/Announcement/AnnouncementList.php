<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Announcement;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Announcement\AnnouncementListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/announcement/list',
            name: 'get_announcement_list',
            openapi: new Operation(
                parameters: [
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
            provider: AnnouncementListProvider::class,
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
    public string $csrfToken = '';

    public function getId(): string
    {
        return $this->id;
    }
}
