<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
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
            status: Response::HTTP_NO_CONTENT,
            openapi: new Operation(
                summary: 'Create a Wiki category',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: 'post_wiki_category',
            processor: WikiCategoryProcessor::class,
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
        new Patch(
            uriTemplate: '/wiki/categories/{categoryId}',
            requirements: ['categoryId' => '\d+'],
            status: Response::HTTP_NO_CONTENT,
            openapi: new Operation(
                summary: 'Update a Wiki category',
                parameters: [
                    new Parameter(name: 'categoryId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: 'put_wiki_category',
            processor: WikiCategoryProcessor::class,
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
        new Post(
            uriTemplate: '/wiki/categories/{categoryId}/delete',
            requirements: ['categoryId' => '\d+'],
            status: Response::HTTP_NO_CONTENT,
            openapi: new Operation(
                summary: 'Delete a Wiki category and its descendants',
                parameters: [
                    new Parameter(name: 'categoryId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: 'post_wiki_category_delete',
            processor: WikiCategoryProcessor::class,
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

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }
}
