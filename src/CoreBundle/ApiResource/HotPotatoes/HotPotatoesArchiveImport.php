<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\HotPotatoes;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\Controller\Api\HotPotatoesArchiveImportAction;
use Chamilo\CoreBundle\State\HotPotatoes\HotPotatoesArchiveImportProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/hotpotatoes/archives/import',
            controller: HotPotatoesArchiveImportAction::class,
            openapi: new Operation(
                summary: 'Store a Hot Potatoes ZIP archive as a private course resource (no HTML playback)',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'package' => ['type' => 'string', 'format' => 'binary'],
                                ],
                                'required' => ['package'],
                            ],
                        ],
                    ]),
                    required: true,
                ),
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            read: false,
            deserialize: false,
            output: false,
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
            processor: HotPotatoesArchiveImportProcessor::class,
        ),
    ],
)]
final class HotPotatoesArchiveImport {}
