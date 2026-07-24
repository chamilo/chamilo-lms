<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Wiki\WikiCategoryProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiCategoryInput',
    operations: [
        new Post(
            uriTemplate: '/wiki/categories',
            openapi: new Operation(
                summary: 'Create a Wiki category',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_wiki_category',
            processor: WikiCategoryProcessor::class,
        ),
        new Patch(
            uriTemplate: '/wiki/categories/{categoryId}',
            requirements: ['categoryId' => '\d+'],
            openapi: new Operation(
                summary: 'Update a Wiki category',
                parameters: [
                    new Parameter(name: 'categoryId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'put_wiki_category',
            processor: WikiCategoryProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/categories/{categoryId}/delete',
            requirements: ['categoryId' => '\d+'],
            openapi: new Operation(
                summary: 'Delete a Wiki category and its descendants',
                parameters: [
                    new Parameter(name: 'categoryId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_wiki_category_delete',
            processor: WikiCategoryProcessor::class,
        ),
    ],
    denormalizationContext: ['groups' => ['wiki_category:write']],
)]
final class WikiCategoryInput
{
    #[ApiProperty(identifier: true)]
    public ?int $categoryId = null;

    #[Groups(['wiki_category:write'])]
    public string $title = '';

    #[Groups(['wiki_category:write'])]
    public ?int $parentId = null;

    #[Groups(['wiki_category:write'])]
    public string $csrfToken = '';

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }
}
