<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Wiki\WikiCategoryProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiCategoryCollection',
    operations: [
        new Get(
            uriTemplate: '/wiki/categories',
            openapi: new Operation(
                summary: 'Read Wiki categories for the current course and session',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_wiki_categories',
            provider: WikiCategoryProvider::class,
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
    normalizationContext: ['groups' => ['wiki_categories:read']],
)]
final class WikiCategoryCollection
{
    #[ApiProperty(identifier: true)]
    #[Groups(['wiki_categories:read'])]
    public string $id = 'wiki_categories';

    #[Groups(['wiki_categories:read'])]
    public bool $enabled = false;

    #[Groups(['wiki_categories:read'])]
    public bool $canManage = false;

    /**
     * @var array<int, array{id:int, title:string, label:string, pathTitle:string, parentId:?int, level:int, pageCount:int, descendantCount:int}>
     */
    #[Groups(['wiki_categories:read'])]
    public array $categories = [];

    public function getId(): string
    {
        return $this->id;
    }
}
