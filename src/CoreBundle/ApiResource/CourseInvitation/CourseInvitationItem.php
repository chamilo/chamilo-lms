<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseInvitation;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\Entity\CourseInvitation;
use Chamilo\CoreBundle\State\CourseInvitation\CourseInvitationProvider;
use Chamilo\CoreBundle\State\CourseInvitation\CourseInvitationRevokeProcessor;
use Chamilo\CoreBundle\State\CourseInvitation\CourseInvitationSendProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

use const DATE_ATOM;

/**
 * Doubles as: a collection item (list of invitations already sent for the
 * current cid/sid context) and the "form" response (context info for the
 * send form) — same combined-shape convention already used by
 * CourseDescriptionItem for its read/write/meta fields.
 */
#[ApiResource(
    shortName: 'CourseInvitation',
    operations: [
        new Get(
            uriTemplate: '/course-invitation/form',
            security: "is_granted('ROLE_USER')",
            name: 'get_course_invitation_form',
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
            provider: CourseInvitationProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/course-invitations',
            security: "is_granted('ROLE_USER')",
            name: 'get_course_invitations',
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
            provider: CourseInvitationProvider::class,
        ),
        new Post(
            uriTemplate: '/course-invitation',
            security: "is_granted('ROLE_USER')",
            input: CourseInvitationWriteInput::class,
            read: false,
            name: 'post_course_invitation',
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
            processor: CourseInvitationSendProcessor::class,
        ),
        new Delete(
            uriTemplate: '/course-invitation/{id}',
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_USER')",
            name: 'delete_course_invitation',
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
            provider: CourseInvitationProvider::class,
            processor: CourseInvitationRevokeProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_invitation:read']],
)]
final class CourseInvitationItem
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_invitation:read'])]
    public ?int $id = null;

    #[Groups(['course_invitation:read'])]
    public string $email = '';

    #[Groups(['course_invitation:read'])]
    public bool $isSessionInvitation = false;

    #[Groups(['course_invitation:read'])]
    public string $targetTitle = '';

    #[Groups(['course_invitation:read'])]
    public string $createdAt = '';

    #[Groups(['course_invitation:read'])]
    public ?string $acceptedAt = null;

    #[Groups(['course_invitation:read'])]
    public ?string $revokedAt = null;

    /**
     * Only set for a still-pending invitation (null once accepted or
     * revoked, since its one-time token no longer exists at that point).
     * Populated by the Provider, not fromInvitation() below, since building
     * it requires looking up the associated ValidationToken.
     */
    #[Groups(['course_invitation:read'])]
    public ?string $invitationUrl = null;

    /**
     * Only populated by the "form" (Get) operation.
     */
    #[Groups(['course_invitation:read'])]
    public bool $isSessionContext = false;

    #[Groups(['course_invitation:read'])]
    public string $contextTitle = '';

    public static function fromInvitation(CourseInvitation $invitation): self
    {
        $resource = new self();
        $resource->id = $invitation->getId();
        $resource->email = $invitation->getEmail();
        $resource->isSessionInvitation = $invitation->isSessionInvitation();
        $resource->targetTitle = $invitation->isSessionInvitation()
            ? (string) $invitation->getSession()?->getTitle()
            : (string) $invitation->getCourse()?->getTitle();
        $resource->createdAt = $invitation->getCreatedAt()->format(DATE_ATOM);
        $resource->acceptedAt = $invitation->getAcceptedAt()?->format(DATE_ATOM);
        $resource->revokedAt = $invitation->getRevokedAt()?->format(DATE_ATOM);

        return $resource;
    }
}
