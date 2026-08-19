<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Gradebook\GradebookCommentActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookCommentAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/comments/action',
            openapi: new Operation(
                summary: 'Create or update a learner Gradebook comment in the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'post_gradebook_comment_action',
            processor: GradebookCommentActionProcessor::class,
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
            ],
        ),
    ],
    normalizationContext: ['groups' => ['gradebook_comment_action:read']],
    denormalizationContext: ['groups' => ['gradebook_comment_action:write']],
)]
final class GradebookCommentAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_comment_action:read'])]
    public string $id = 'gradebook_comment_action';

    #[Groups(['gradebook_comment_action:write'])]
    public int $categoryId = 0;

    #[Groups(['gradebook_comment_action:write'])]
    public int $userId = 0;

    #[Groups(['gradebook_comment_action:read', 'gradebook_comment_action:write'])]
    public string $comment = '';

    #[Groups(['gradebook_comment_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['gradebook_comment_action:read'])]
    public bool $success = false;

    public function getId(): string
    {
        return $this->id;
    }
}
