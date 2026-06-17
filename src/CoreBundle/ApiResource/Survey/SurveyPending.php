<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Survey\SurveyPendingProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/survey/pending',
            name: 'get_survey_pending',
            openapi: new Operation(
                summary: 'Pending surveys for the current user',
            ),
            provider: SurveyPendingProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: [
        'groups' => ['survey_pending:read'],
    ],
)]
final class SurveyPending
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_pending:read'])]
    public string $id = 'survey_pending';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_pending:read'])]
    public array $items = [];

    #[Groups(['survey_pending:read'])]
    public int $totalItems = 0;

    public function getId(): string
    {
        return $this->id;
    }
}
