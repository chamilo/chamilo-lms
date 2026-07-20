<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Wiki\WikiPageHistoryProvider;
use Chamilo\CoreBundle\State\Wiki\WikiPageRestoreProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiPageHistory',
    operations: [
        new Get(
            uriTemplate: '/wiki/page/{pageId}/history',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Read Wiki page versions and optional differences',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'versionIid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'oldIid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'newIid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(
                        name: 'mode',
                        in: 'query',
                        required: false,
                        schema: ['type' => 'string', 'enum' => ['line', 'word']],
                    ),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_wiki_page_history',
            provider: WikiPageHistoryProvider::class,
        ),
        new Post(
            uriTemplate: '/wiki/page/{pageId}/restore',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Restore a historical Wiki page version as a new version',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_wiki_page_restore',
            processor: WikiPageRestoreProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['wiki_page_history:read']],
    denormalizationContext: ['groups' => ['wiki_page_history:write']],
)]
final class WikiPageHistory
{
    #[ApiProperty(identifier: true)]
    #[Groups(['wiki_page_history:read'])]
    public ?int $pageId = null;

    #[Groups(['wiki_page_history:read'])]
    public int $courseId = 0;

    #[Groups(['wiki_page_history:read'])]
    public ?int $sessionId = null;

    #[Groups(['wiki_page_history:read'])]
    public ?int $groupId = null;

    #[Groups(['wiki_page_history:read'])]
    public int $nodeId = 0;

    #[Groups(['wiki_page_history:read'])]
    public string $reflink = '';

    #[Groups(['wiki_page_history:read'])]
    public string $title = '';

    #[Groups(['wiki_page_history:read'])]
    public bool $isInheritedFromCourse = false;

    #[Groups(['wiki_page_history:read'])]
    public ?int $currentIid = null;

    #[Groups(['wiki_page_history:read'])]
    public int $currentVersion = 0;

    #[Groups(['wiki_page_history:read'])]
    public bool $canRestore = false;

    #[Groups(['wiki_page_history:read', 'wiki_page_history:write'])]
    public string $csrfToken = '';

    #[Groups(['wiki_page_history:write'])]
    public ?int $versionIid = null;

    /**
     * @var array<int, array<string, int|string|bool|null>>
     */
    #[Groups(['wiki_page_history:read'])]
    public array $versions = [];

    /**
     * @var array<string, int|string|bool|null>|null
     */
    #[Groups(['wiki_page_history:read'])]
    public ?array $selectedVersion = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['wiki_page_history:read'])]
    public ?array $comparison = null;

    #[Groups(['wiki_page_history:read'])]
    public ?int $restoredIid = null;

    #[Groups(['wiki_page_history:read'])]
    public ?int $restoredVersion = null;

    public function getPageId(): ?int
    {
        return $this->pageId;
    }
}
