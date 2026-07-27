<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Mobile;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\Mobile\MobileAssignmentSubmissionDeleteProcessor;
use Chamilo\CoreBundle\State\Mobile\MobileAssignmentSubmissionProcessor;
use Chamilo\CoreBundle\State\Mobile\MobileAssignmentSubmissionUpdateProcessor;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use LogicException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

use const DATE_ATOM;

#[ApiResource(
    shortName: 'MobileAssignmentSubmission',
    operations: [
        new Post(
            uriTemplate: '/mobile_assignment_submissions',
            status: Response::HTTP_CREATED,
            security: "is_granted('ROLE_STUDENT') or is_granted('ROLE_STUDENT_BOSS')",
            input: MobileAssignmentSubmissionInput::class,
            read: false,
            name: 'create_mobile_assignment_submission',
            processor: MobileAssignmentSubmissionProcessor::class,
        ),
        new Patch(
            uriTemplate: '/mobile_assignment_submissions/{id}',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_OK,
            security: "is_granted('ROLE_STUDENT') or is_granted('ROLE_STUDENT_BOSS')",
            input: MobileAssignmentSubmissionUpdateInput::class,
            read: false,
            name: 'update_mobile_assignment_submission',
            processor: MobileAssignmentSubmissionUpdateProcessor::class,
        ),
        new Delete(
            uriTemplate: '/mobile_assignment_submissions/{id}',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('ROLE_STUDENT') or is_granted('ROLE_STUDENT_BOSS')",
            input: false,
            read: false,
            output: false,
            name: 'delete_mobile_assignment_submission',
            processor: MobileAssignmentSubmissionDeleteProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['mobile_assignment_submission:read']],
)]
final class MobileAssignmentSubmission
{
    #[ApiProperty(identifier: true)]
    #[Groups(['mobile_assignment_submission:read'])]
    public int $id;

    #[Groups(['mobile_assignment_submission:read'])]
    public string $title;

    #[Groups(['mobile_assignment_submission:read'])]
    public string $submittedAt;

    #[Groups(['mobile_assignment_submission:read'])]
    public bool $hasFile;

    public static function fromSubmission(CStudentPublication $submission): self
    {
        $id = $submission->getIid();
        $sentDate = $submission->getSentDate();

        if (null === $id || null === $sentDate) {
            throw new LogicException('The assignment submission is incomplete.');
        }

        $resource = new self();
        $resource->id = $id;
        $resource->title = $submission->getTitle();
        $resource->submittedAt = $sentDate->format(DATE_ATOM);
        $resource->hasFile = $submission->getContainsFile() > 0;

        return $resource;
    }
}
