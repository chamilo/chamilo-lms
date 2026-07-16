<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Notebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Notebook\NotebookDeleteProcessor;
use Chamilo\CoreBundle\State\Notebook\NotebookItemProcessor;
use Chamilo\CoreBundle\State\Notebook\NotebookItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'NotebookItem',
    operations: [
        new Get(
            uriTemplate: '/notebook/form',
            openapi: new Operation(
                summary: 'Get notebook form data for the current user',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'id', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR') or is_granted('ROLE_SESSION_MANAGER') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'get_notebook_form',
            provider: NotebookItemProvider::class,
        ),
        new Post(
            uriTemplate: '/notebook',
            openapi: new Operation(
                summary: 'Create a personal notebook entry',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR') or is_granted('ROLE_SESSION_MANAGER') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'post_notebook',
            processor: NotebookItemProcessor::class,
        ),
        new Put(
            uriTemplate: '/notebook/{iid}',
            requirements: ['iid' => '\d+'],
            openapi: new Operation(
                summary: 'Update a personal notebook entry',
                parameters: [
                    new Parameter(name: 'iid', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR') or is_granted('ROLE_SESSION_MANAGER') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'put_notebook',
            processor: NotebookItemProcessor::class,
        ),
        new Delete(
            uriTemplate: '/notebook/{iid}',
            requirements: ['iid' => '\d+'],
            openapi: new Operation(
                summary: 'Delete a personal notebook entry',
                parameters: [
                    new Parameter(name: 'iid', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_HR') or is_granted('ROLE_SESSION_MANAGER') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'delete_notebook',
            provider: NotebookItemProvider::class,
            processor: NotebookDeleteProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['notebook_item:read']],
    denormalizationContext: ['groups' => ['notebook_item:write']],
)]
final class NotebookItem
{
    #[ApiProperty(identifier: true)]
    #[Groups(['notebook_item:read', 'notebook_item:write'])]
    public ?int $iid = null;

    #[Groups(['notebook_item:read', 'notebook_item:write'])]
    public string $title = '';

    #[Groups(['notebook_item:read', 'notebook_item:write'])]
    public string $content = '';

    #[Groups(['notebook_item:read', 'notebook_item:write'])]
    public string $language = '';

    #[Groups(['notebook_item:read', 'notebook_item:write'])]
    public string $csrfToken = '';

    #[Groups(['notebook_item:read'])]
    public bool $canWrite = false;

    #[Groups(['notebook_item:read'])]
    public bool $isNew = true;

    #[Groups(['notebook_item:read'])]
    public bool $fullEditor = false;

    /**
     * @var array<int, array<string, string>>
     */
    #[Groups(['notebook_item:read'])]
    public array $languages = [];

    public function getIid(): ?int
    {
        return $this->iid;
    }
}
