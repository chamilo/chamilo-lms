<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
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
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_wiki_categories',
            provider: WikiCategoryProvider::class,
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

    #[Groups(['wiki_categories:read'])]
    public string $csrfToken = '';

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
