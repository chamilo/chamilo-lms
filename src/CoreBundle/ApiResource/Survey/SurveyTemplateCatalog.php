<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\Survey\SurveyTemplateCatalogProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyTemplateCatalog',
    operations: [
        new Get(
            uriTemplate: '/survey/templates',
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_survey_template_catalog',
            provider: SurveyTemplateCatalogProvider::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Current course identifier for teacher access',
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Current session identifier for teacher access',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['survey_template_catalog:read']],
)]
final class SurveyTemplateCatalog
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_template_catalog:read'])]
    public string $id = 'survey_template_catalog';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_template_catalog:read'])]
    public array $templates = [];

    #[Groups(['survey_template_catalog:read'])]
    public bool $isAdmin = false;

    public function getId(): string
    {
        return $this->id;
    }
}
