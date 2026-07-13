<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Notebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Notebook\NotebookListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'NotebookList',
    operations: [
        new Get(
            uriTemplate: '/notebook/list',
            openapi: new Operation(
                summary: 'List the current user notebook entries in a course context',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(
                        name: 'sort',
                        in: 'query',
                        required: false,
                        schema: [
                            'type' => 'string',
                            'enum' => ['creation_date', 'update_date', 'title'],
                        ],
                    ),
                    new Parameter(
                        name: 'direction',
                        in: 'query',
                        required: false,
                        schema: [
                            'type' => 'string',
                            'enum' => ['ASC', 'DESC'],
                        ],
                    ),
                ],
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR') or is_granted('ROLE_SESSION_MANAGER') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'get_notebook_list',
            provider: NotebookListProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['notebook_list:read']],
)]
final class NotebookList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['notebook_list:read'])]
    public string $id = 'notebook_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['notebook_list:read'])]
    public array $items = [];

    #[Groups(['notebook_list:read'])]
    public int $totalItems = 0;

    #[Groups(['notebook_list:read'])]
    public int $courseId = 0;

    #[Groups(['notebook_list:read'])]
    public ?int $sessionId = null;

    #[Groups(['notebook_list:read'])]
    public bool $canWrite = false;

    #[Groups(['notebook_list:read'])]
    public bool $studentView = false;

    #[Groups(['notebook_list:read'])]
    public string $csrfToken = '';

    #[Groups(['notebook_list:read'])]
    public string $sort = 'creation_date';

    #[Groups(['notebook_list:read'])]
    public string $direction = 'ASC';

    public function getId(): string
    {
        return $this->id;
    }
}
