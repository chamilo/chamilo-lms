<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\CourseInvitationRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * A pending or accepted course/session invitation sent to an email address.
 * Two modes:
 * - registration invite (invited_user_id NULL): unknown email opens registration.php
 * - existing-user invite (invited_user_id set): bound to that account, redeem via
 *   /course-invitation/accept after login.
 *
 * Referenced by a ValidationToken (type COURSE_INVITATION) via that token's
 * resourceId, which carries the one-time secret hash; this entity carries
 * what the hash actually grants.
 *
 * Course and session subscription are mutually exclusive in Chamilo (you can
 * subscribe to a base course, or to a whole session, never "this course
 * within this session" as an independent action). So: when session is set,
 * it governs the subscribe action (whole-session invitation) and course (if
 * also set) is purely informational — which course's Users tool the
 * invitation was sent from. When session is null, course governs a plain
 * course subscription.
 *
 * Pinned to the AccessUrl it was issued from (a Chamilo instance can host
 * several separate portals on the same DB). Without this, a link sent from
 * portal A could be redeemed by opening it on portal B's domain, registering
 * the new account under the wrong portal — leaking cross-tenant data.
 */
#[ORM\Table(name: 'course_invitation')]
#[ORM\Index(columns: ['email'], name: 'idx_course_invitation_email')]
#[ORM\Entity(repositoryClass: CourseInvitationRepository::class)]
class CourseInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255)]
    protected string $email;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Course $course = null;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Session $session = null;

    #[ORM\Column(name: 'exercise_id', type: 'integer', nullable: true)]
    protected ?int $exerciseId = null;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class)]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected AccessUrl $accessUrl;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $createdBy;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'accepted_at', type: 'datetime', nullable: true)]
    protected ?DateTime $acceptedAt = null;

    #[ORM\Column(name: 'revoked_at', type: 'datetime', nullable: true)]
    protected ?DateTime $revokedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'registered_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $registeredUser = null;

    /**
     * When set, this invitation targets an existing platform account: the
     * redeem link requires that user to log in and then auto-subscribes them.
     * Null means a classic registration invitation for an unknown email.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'invited_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $invitedUser = null;

    public function __construct(string $email, User $createdBy, AccessUrl $accessUrl)
    {
        $this->email = $email;
        $this->createdBy = $createdBy;
        $this->accessUrl = $accessUrl;
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }

    public function setExerciseId(?int $exerciseId): self
    {
        $this->exerciseId = $exerciseId;

        return $this;
    }

    public function getAccessUrl(): AccessUrl
    {
        return $this->accessUrl;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getAcceptedAt(): ?DateTime
    {
        return $this->acceptedAt;
    }

    public function getRevokedAt(): ?DateTime
    {
        return $this->revokedAt;
    }

    public function getRegisteredUser(): ?User
    {
        return $this->registeredUser;
    }

    public function getInvitedUser(): ?User
    {
        return $this->invitedUser;
    }

    public function setInvitedUser(?User $invitedUser): self
    {
        $this->invitedUser = $invitedUser;

        return $this;
    }

    public function isForExistingUser(): bool
    {
        return null !== $this->invitedUser;
    }

    public function isAccepted(): bool
    {
        return null !== $this->acceptedAt;
    }

    public function isRevoked(): bool
    {
        return null !== $this->revokedAt;
    }

    /**
     * Marks the invitation as redeemed by a newly registered user.
     */
    public function accept(User $registeredUser): self
    {
        $this->acceptedAt = new DateTime();
        $this->registeredUser = $registeredUser;

        return $this;
    }

    /**
     * Marks the invitation as revoked by whoever sent it, so the link can no
     * longer be redeemed. Only meaningful while still pending — an accepted
     * invitation already has a registered account and cannot be undone here.
     */
    public function revoke(): self
    {
        $this->revokedAt = new DateTime();

        return $this;
    }

    /**
     * True when a whole-session invitation (session governs the subscribe
     * action); false for a plain course invitation.
     */
    public function isSessionInvitation(): bool
    {
        return null !== $this->session;
    }
}
