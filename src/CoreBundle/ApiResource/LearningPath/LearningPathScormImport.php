<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\Controller\Api\LearningPathScormImportAction;
use Chamilo\CoreBundle\State\LearningPath\LearningPathScormImportProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_paths/scorm/import',
            controller: LearningPathScormImportAction::class,
            processor: LearningPathScormImportProcessor::class,
            openapi: new Operation(
                summary: 'Import a SCORM package into the current course context',
                requestBody: new RequestBody(
                    required: true,
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'package' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                    ],
                                    'csrfToken' => ['type' => 'string'],
                                    'useMaxScore' => ['type' => 'boolean', 'default' => true],
                                    'contentProximity' => [
                                        'type' => 'string',
                                        'enum' => ['local', 'remote'],
                                        'default' => 'local',
                                    ],
                                    'contentMaker' => ['type' => 'string', 'default' => 'Scorm'],
                                    'allowHtaccess' => ['type' => 'boolean', 'default' => false],
                                ],
                                'required' => ['package', 'csrfToken'],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            read: false,
            deserialize: false,
            output: false,
        ),
    ],
)]
final class LearningPathScormImport
{
}
