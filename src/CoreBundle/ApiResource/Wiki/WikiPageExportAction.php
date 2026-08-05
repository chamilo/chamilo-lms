<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Wiki\WikiPageExportProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiPageExportAction',
    operations: [
        new Post(
            uriTemplate: '/wiki/page/{pageId}/export/document',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Export a Wiki page to the Documents tool',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: self::OPERATION_EXPORT_DOCUMENT,
            processor: WikiPageExportProcessor::class,
        ),
    ],
    denormalizationContext: ['groups' => ['wiki_page_export:write']],
)]
final class WikiPageExportAction
{
    public const string OPERATION_EXPORT_DOCUMENT = 'post_wiki_page_export_document';

    #[ApiProperty(identifier: true)]
    public ?int $pageId = null;

    #[Groups(['wiki_page_export:write'])]
    public string $csrfToken = '';

    public function getPageId(): ?int
    {
        return $this->pageId;
    }
}
