<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\Controller\Api\LearningPathScormPackageAction;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_paths/{lpId}/scorm/package',
            requirements: ['lpId' => '\\d+'],
            controller: LearningPathScormPackageAction::class,
            openapi: new Operation(
                summary: 'Download the original package of a SCORM learning path',
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            output: false,
        ),
    ],
)]
final class LearningPathScormPackage
{
}
