<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\Controller\Api\LearningPathScormUpdateAction;
use Chamilo\CoreBundle\State\LearningPath\LearningPathScormUpdateProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_paths/{lpId}/scorm/update',
            requirements: ['lpId' => '\\d+'],
            controller: LearningPathScormUpdateAction::class,
            processor: LearningPathScormUpdateProcessor::class,
            openapi: new Operation(
                summary: 'Replace the package files of an existing SCORM learning path',
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
final class LearningPathScormUpdate
{
}
