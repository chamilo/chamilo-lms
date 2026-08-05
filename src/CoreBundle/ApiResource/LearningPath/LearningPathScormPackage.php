<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\Controller\Api\LearningPathScormPackageAction;
use Chamilo\CoreBundle\Controller\Api\LearningPathScormRuntimePackageAction;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_paths/{lpId}/scorm/package',
            requirements: ['lpId' => '\d+'],
            controller: LearningPathScormPackageAction::class,
            openapi: new Operation(
                summary: 'Download the original package of a SCORM learning path',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
        ),
        new Get(
            uriTemplate: '/learning_paths/{lpId}/runtime/scorm/package',
            requirements: ['lpId' => '\d+'],
            controller: LearningPathScormRuntimePackageAction::class,
            openapi: new Operation(
                summary: 'Download an authorized SCORM package for the active runtime item',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            outputFormats: [
                'zip' => ['application/zip'],
            ],
            output: false,
            read: false,
            name: 'download_learning_path_scorm_runtime_package',
        ),
    ],
)]
final class LearningPathScormPackage {}
