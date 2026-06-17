<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Survey\SurveyInvitationProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyInvitationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyInvitation',
    operations: [
        new Get(
            uriTemplate: '/survey/invitations/{surveyId}',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Survey invitation data',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_survey_invitations',
            provider: SurveyInvitationProvider::class,
        ),
        new Post(
            uriTemplate: '/survey/invitations/{surveyId}/publish',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Publish survey invitations',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_invitations_publish',
            processor: SurveyInvitationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_invitation:read']],
    denormalizationContext: ['groups' => ['survey_invitation:write']],
)]
final class SurveyInvitation
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_invitation:read', 'survey_invitation:write'])]
    public ?int $surveyId = null;

    #[Groups(['survey_invitation:read', 'survey_invitation:write'])]
    public string $csrfToken = '';

    #[Groups(['survey_invitation:read'])]
    public bool $canManage = false;

    #[Groups(['survey_invitation:read'])]
    public string $message = '';

    #[Groups(['survey_invitation:read'])]
    public string $anonymousLink = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_invitation:read'])]
    public array $survey = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_invitation:read'])]
    public array $settings = [];

    /**
     * @var array<string, int>
     */
    #[Groups(['survey_invitation:read'])]
    public array $counts = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_invitation:read'])]
    public array $users = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_invitation:read'])]
    public array $groups = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_invitation:read'])]
    public array $invitations = [];

    #[Groups(['survey_invitation:read', 'survey_invitation:write'])]
    public string $mailSubject = '';

    #[Groups(['survey_invitation:read', 'survey_invitation:write'])]
    public string $mailText = '';

    #[Groups(['survey_invitation:write'])]
    public bool $sendMail = false;

    #[Groups(['survey_invitation:write'])]
    public bool $resendToAll = false;

    #[Groups(['survey_invitation:write'])]
    public bool $remindUnanswered = false;

    #[Groups(['survey_invitation:write'])]
    public bool $hideLink = false;

    /**
     * @var array<int, int>
     */
    #[Groups(['survey_invitation:read', 'survey_invitation:write'])]
    public array $selectedUserIds = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['survey_invitation:read', 'survey_invitation:write'])]
    public array $selectedGroupIds = [];

    /**
     * @var array<int, string>
     */
    #[Groups(['survey_invitation:write'])]
    public array $additionalEmails = [];

    public function getSurveyId(): ?int
    {
        return $this->surveyId;
    }
}
