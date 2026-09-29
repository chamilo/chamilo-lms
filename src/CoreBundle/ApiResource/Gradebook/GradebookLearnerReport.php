<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Gradebook\GradebookLearnerReportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookLearnerReport',
    operations: [
        new Get(
            uriTemplate: '/gradebook/learner-report',
            openapi: new Operation(
                summary: 'Detailed Gradebook report for one learner in the current course context',
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_STUDENT')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_learner_report',
            provider: GradebookLearnerReportProvider::class,
            // Declared here rather than in the openapi operation: a QueryParameter
            // documents AND validates, while an openapi Parameter only documents,
            // and a twin declaration there would hide this one.
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
                    description: 'Group identifier. The group must belong to the course',
                ),
                'node' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Resource node of the course, which the provider checks against the course itself',
                    required: true,
                ),
                'categoryId' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Gradebook category to report on. Defaults to the root category of the course',
                ),
                'userId' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Learner to report on. Defaults to the current user, who must be a learner of the course',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['gradebook_learner_report:read']],
)]
final class GradebookLearnerReport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_learner_report:read'])]
    public string $id = 'gradebook_learner_report';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_learner_report:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_learner_report:read'])]
    public ?array $category = null;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_learner_report:read'])]
    public array $learner = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_learner_report:read'])]
    public array $rows = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_learner_report:read'])]
    public ?array $total = null;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_learner_report:read'])]
    public array $settings = [];

    #[Groups(['gradebook_learner_report:read'])]
    public string $comment = '';

    #[Groups(['gradebook_learner_report:read'])]
    public string $commentCsrfToken = '';

    #[Groups(['gradebook_learner_report:read'])]
    public bool $canManage = false;

    public function getId(): string
    {
        return $this->id;
    }
}
