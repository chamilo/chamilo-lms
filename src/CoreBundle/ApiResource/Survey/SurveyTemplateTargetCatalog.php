<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\Survey\SurveyTemplateTargetCatalogProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyTemplateTargetCatalog',
    operations: [
        new Get(
            uriTemplate: '/survey/templates/targets',
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_survey_template_target_catalog',
            provider: SurveyTemplateTargetCatalogProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_template_target_catalog:read']],
)]
final class SurveyTemplateTargetCatalog
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_template_target_catalog:read'])]
    public string $id = 'survey_template_target_catalog';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_template_target_catalog:read'])]
    public array $courses = [];

    public function getId(): string
    {
        return $this->id;
    }
}
