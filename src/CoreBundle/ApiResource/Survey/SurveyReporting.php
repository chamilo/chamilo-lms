<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Survey\SurveyReportingProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyReporting',
    operations: [
        new Get(
            uriTemplate: '/survey/reporting/{surveyId}',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Survey reporting data',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'user', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_ADMIN')",
            name: 'get_survey_reporting',
            provider: SurveyReportingProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_reporting:read']],
)]
final class SurveyReporting
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_reporting:read'])]
    public ?int $surveyId = null;

    #[Groups(['survey_reporting:read'])]
    public bool $canView = false;

    #[Groups(['survey_reporting:read'])]
    public bool $canExport = false;

    #[Groups(['survey_reporting:read'])]
    public string $message = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_reporting:read'])]
    public array $survey = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_reporting:read'])]
    public array $settings = [];

    /**
     * @var array<string, int>
     */
    #[Groups(['survey_reporting:read'])]
    public array $counts = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_reporting:read'])]
    public array $reportTypes = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_reporting:read'])]
    public array $questionReports = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_reporting:read'])]
    public array $users = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_reporting:read'])]
    public array $selectedUser = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_reporting:read'])]
    public array $userAnswers = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_reporting:read'])]
    public array $completeRows = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['survey_reporting:read'])]
    public array $exportUrls = [];

    public function getSurveyId(): ?int
    {
        return $this->surveyId;
    }
}
