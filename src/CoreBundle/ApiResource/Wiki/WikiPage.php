<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Wiki\WikiPageProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiPage',
    operations: [
        new Get(
            uriTemplate: '/wiki/page',
            openapi: new Operation(
                summary: 'Read the current logical Wiki page',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'title', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_wiki_page',
            provider: WikiPageProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['wiki_page:read']],
)]
final class WikiPage
{
    #[ApiProperty(identifier: true)]
    #[Groups(['wiki_page:read'])]
    public string $id = 'wiki_page';

    #[Groups(['wiki_page:read'])]
    public int $courseId = 0;

    #[Groups(['wiki_page:read'])]
    public ?int $sessionId = null;

    #[Groups(['wiki_page:read'])]
    public ?int $groupId = null;

    #[Groups(['wiki_page:read'])]
    public int $nodeId = 0;

    #[Groups(['wiki_page:read'])]
    public string $reflink = 'index';

    #[Groups(['wiki_page:read'])]
    public bool $exists = false;

    #[Groups(['wiki_page:read'])]
    public bool $isInheritedFromCourse = false;

    #[Groups(['wiki_page:read'])]
    public ?int $sourceSessionId = null;

    #[Groups(['wiki_page:read'])]
    public ?int $iid = null;

    #[Groups(['wiki_page:read'])]
    public ?int $pageId = null;

    #[Groups(['wiki_page:read'])]
    public ?int $version = null;

    #[Groups(['wiki_page:read'])]
    public string $title = '';

    #[Groups(['wiki_page:read'])]
    public string $content = '';

    #[Groups(['wiki_page:read'])]
    public ?string $updatedAt = null;

    #[Groups(['wiki_page:read'])]
    public ?int $authorId = null;

    #[Groups(['wiki_page:read'])]
    public string $authorName = '';

    #[Groups(['wiki_page:read'])]
    public int $assignment = 0;

    #[Groups(['wiki_page:read'])]
    public bool $hasTask = false;

    #[Groups(['wiki_page:read'])]
    public string $task = '';

    #[Groups(['wiki_page:read'])]
    public string $feedback = '';

    #[Groups(['wiki_page:read'])]
    public string $assignmentOwnerName = '';

    #[Groups(['wiki_page:read'])]
    public ?string $assignmentStartDate = null;

    #[Groups(['wiki_page:read'])]
    public ?string $assignmentEndDate = null;

    #[Groups(['wiki_page:read'])]
    public bool $delayedSubmit = false;

    #[Groups(['wiki_page:read'])]
    public int $maxWords = 0;

    #[Groups(['wiki_page:read'])]
    public int $maxVersions = 0;

    #[Groups(['wiki_page:read'])]
    public bool $assignmentNotStarted = false;

    #[Groups(['wiki_page:read'])]
    public bool $assignmentLate = false;

    #[Groups(['wiki_page:read'])]
    public bool $assignmentClosed = false;

    #[Groups(['wiki_page:read'])]
    public int $progress = 0;

    #[Groups(['wiki_page:read'])]
    public ?int $score = null;

    #[Groups(['wiki_page:read'])]
    public int $wordCount = 0;

    #[Groups(['wiki_page:read'])]
    public int $hits = 0;

    #[Groups(['wiki_page:read'])]
    public bool $visible = true;

    #[Groups(['wiki_page:read'])]
    public bool $editLocked = false;

    #[Groups(['wiki_page:read'])]
    public bool $addLocked = false;

    #[Groups(['wiki_page:read'])]
    public bool $subscribed = false;

    #[Groups(['wiki_page:read'])]
    public bool $canManage = false;

    #[Groups(['wiki_page:read'])]
    public bool $canChangeVisibility = false;

    #[Groups(['wiki_page:read'])]
    public bool $canChangeProtection = false;

    #[Groups(['wiki_page:read'])]
    public bool $canChangeAddLock = false;

    #[Groups(['wiki_page:read'])]
    public bool $canSubscribe = false;

    #[Groups(['wiki_page:read'])]
    public bool $canDiscuss = false;

    #[Groups(['wiki_page:read'])]
    public bool $canDelete = false;

    #[Groups(['wiki_page:read'])]
    public bool $canExportPdf = false;

    #[Groups(['wiki_page:read'])]
    public bool $canExportToDocuments = false;

    #[Groups(['wiki_page:read'])]
    public bool $canPrint = false;

    #[Groups(['wiki_page:read'])]
    public string $managementCsrfToken = '';

    #[Groups(['wiki_page:read'])]
    public bool $canCreate = false;

    #[Groups(['wiki_page:read'])]
    public bool $canEdit = false;

    #[Groups(['wiki_page:read'])]
    public bool $studentView = false;

    /**
     * @var array<int, array{id:int, title:string, pathTitle:string}>
     */
    #[Groups(['wiki_page:read'])]
    public array $categories = [];

    #[Groups(['wiki_page:read'])]
    public bool $categoriesEnabled = false;

    #[Groups(['wiki_page:read'])]
    public bool $canManageCategories = false;

    #[Groups(['wiki_page:read'])]
    public bool $canManageSettings = false;

    /**
     * @var array<string, bool>
     */
    #[Groups(['wiki_page:read'])]
    public array $settings = [];

    public function getId(): string
    {
        return $this->id;
    }
}
