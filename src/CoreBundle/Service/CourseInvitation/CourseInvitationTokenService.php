<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseInvitation;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseInvitation;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\ValidationToken;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\EventLoggerHelper;
use Chamilo\CoreBundle\Helpers\ValidationTokenHelper;
use Chamilo\CoreBundle\Repository\CourseInvitationRepository;
use Chamilo\CoreBundle\Repository\ValidationTokenRepository;
use DateInterval;
use DateTime;
use InvalidArgumentException;

/**
 * Creates, resolves and confirms one-time course/session invitations.
 *
 * The one-time secret lives in the generic ValidationToken table (type
 * TYPE_COURSE_INVITATION); this service is the only thing that knows how to
 * turn that hash into a CourseInvitation row and back. Deliberately does not
 * go through ValidationTokenController/the `/validate/{type}/{hash}` route —
 * that route executes-and-deletes on a single GET, which doesn't fit here:
 * the token must survive from "link opened" through "registration form
 * submitted", not be consumed by merely visiting the link.
 */
final readonly class CourseInvitationTokenService
{
    private const int EXPIRY_DAYS = 7;

    public function __construct(
        private CourseInvitationRepository $invitationRepository,
        private ValidationTokenRepository $tokenRepository,
        private EventLoggerHelper $eventLoggerHelper,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    /**
     * @return array{invitation: CourseInvitation, token: ValidationToken, url: string}
     */
    public function create(
        ?Course $course,
        ?Session $session,
        ?int $exerciseId,
        string $email,
        User $createdBy,
        ?User $invitedUser = null,
    ): array {
        if (null === $course && null === $session) {
            throw new InvalidArgumentException('An invitation needs either a course or a session.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl) {
            throw new InvalidArgumentException('The current access URL could not be resolved.');
        }

        $invitation = new CourseInvitation($email, $createdBy, $accessUrl);
        $invitation->setCourse($course);
        $invitation->setSession($session);
        $invitation->setExerciseId($exerciseId);
        $invitation->setInvitedUser($invitedUser);
        $this->invitationRepository->save($invitation, true);

        $token = new ValidationToken(ValidationTokenHelper::TYPE_COURSE_INVITATION, (int) $invitation->getId());
        $this->tokenRepository->save($token, true);

        $url = $this->buildUrl($token, $invitation);

        $this->eventLoggerHelper->addEvent(
            'course_invitation_sent',
            'course_invitation',
            [
                'email' => $email,
                'invitation_id' => $invitation->getId(),
                'hash' => $token->getHash(),
                'invited_user_id' => $invitedUser?->getId(),
            ],
            null,
            (int) $createdBy->getId(),
            $course?->getId(),
            $session?->getId(),
        );

        return ['invitation' => $invitation, 'token' => $token, 'url' => $url];
    }

    /**
     * @return array{invitation: CourseInvitation, token: ValidationToken}|null
     */
    public function resolve(string $hash): ?array
    {
        $hash = trim($hash);
        if ('' === $hash) {
            return null;
        }

        $token = $this->tokenRepository->findOneBy([
            'type' => ValidationTokenHelper::TYPE_COURSE_INVITATION,
            'hash' => $hash,
        ]);

        if (!$token instanceof ValidationToken) {
            return null;
        }

        $expiresAt = (clone $token->getCreatedAt())->add(new DateInterval('P'.self::EXPIRY_DAYS.'D'));
        if ($expiresAt < new DateTime()) {
            return null;
        }

        $invitation = $this->invitationRepository->find($token->getResourceId());
        if (!$invitation instanceof CourseInvitation || $invitation->isAccepted() || $invitation->isRevoked()) {
            return null;
        }

        // A link issued on one portal must not be redeemable from another
        // portal on the same multi-URL install: treat a mismatch exactly
        // like "not found" rather than a distinguishable error.
        $currentAccessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $currentAccessUrl || $currentAccessUrl->getId() !== $invitation->getAccessUrl()->getId()) {
            return null;
        }

        return ['invitation' => $invitation, 'token' => $token];
    }

    /**
     * The invitation link, for a still-pending invitation only — once
     * accepted or revoked its ValidationToken no longer exists, so there is
     * nothing left to hand out. Lets the sender re-share a link (e.g. over
     * chat) if the invited person never received the email.
     */
    public function getActiveUrl(CourseInvitation $invitation): ?string
    {
        if ($invitation->isAccepted() || $invitation->isRevoked()) {
            return null;
        }

        $token = $this->tokenRepository->findOneBy([
            'type' => ValidationTokenHelper::TYPE_COURSE_INVITATION,
            'resourceId' => $invitation->getId(),
        ]);

        if (!$token instanceof ValidationToken) {
            return null;
        }

        return $this->buildUrl($token, $invitation);
    }

    /**
     * @param array{invitation: CourseInvitation, token: ValidationToken} $resolved
     */
    public function confirm(array $resolved, User $registeredUser): void
    {
        $invitation = $resolved['invitation'];
        $invitation->accept($registeredUser);
        $this->invitationRepository->save($invitation, true);

        $this->tokenRepository->remove($resolved['token'], true);

        $this->eventLoggerHelper->addEvent(
            'course_invitation_used',
            'course_invitation',
            [
                'email' => $invitation->getEmail(),
                'invitation_id' => $invitation->getId(),
                'registered_user_id' => $registeredUser->getId(),
                'invited_user_id' => $invitation->getInvitedUser()?->getId(),
            ],
            null,
            (int) $registeredUser->getId(),
            $invitation->getCourse()?->getId(),
            $invitation->getSession()?->getId(),
        );
    }

    /**
     * Revokes a still-pending invitation: the link stops working immediately
     * (its ValidationToken is deleted, same as on confirm) and the row is
     * kept, marked revoked, for audit visibility in the invitations list.
     */
    public function revoke(CourseInvitation $invitation, User $revokedBy): void
    {
        if ($invitation->isAccepted()) {
            throw new InvalidArgumentException('An accepted invitation cannot be revoked.');
        }

        if ($invitation->isRevoked()) {
            return;
        }

        $invitation->revoke();
        $this->invitationRepository->save($invitation, true);

        $token = $this->tokenRepository->findOneBy([
            'type' => ValidationTokenHelper::TYPE_COURSE_INVITATION,
            'resourceId' => $invitation->getId(),
        ]);
        if ($token instanceof ValidationToken) {
            $this->tokenRepository->remove($token, true);
        }

        $this->eventLoggerHelper->addEvent(
            'course_invitation_revoked',
            'course_invitation',
            [
                'email' => $invitation->getEmail(),
                'invitation_id' => $invitation->getId(),
            ],
            null,
            (int) $revokedBy->getId(),
            $invitation->getCourse()?->getId(),
            $invitation->getSession()?->getId(),
        );
    }

    private function buildUrl(ValidationToken $token, CourseInvitation $invitation): string
    {
        $hash = $token->getHash();

        // Existing accounts go through a login-and-subscribe endpoint; unknown
        // emails still open the registration form with the same token hash.
        if ($invitation->isForExistingUser()) {
            return api_get_path(WEB_PATH).'course-invitation/accept?invitation='.rawurlencode($hash);
        }

        return api_get_path(WEB_CODE_PATH).'auth/registration.php?invitation='.rawurlencode($hash);
    }
}
