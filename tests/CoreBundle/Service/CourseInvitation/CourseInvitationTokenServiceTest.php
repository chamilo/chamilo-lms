<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\CourseInvitation;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\CourseInvitation;
use Chamilo\CoreBundle\Entity\ValidationToken;
use Chamilo\CoreBundle\Helpers\ValidationTokenHelper;
use Chamilo\CoreBundle\Repository\CourseInvitationRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\ValidationTokenRepository;
use Chamilo\CoreBundle\Service\CourseInvitation\CourseInvitationTokenService;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use InvalidArgumentException;

final class CourseInvitationTokenServiceTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private CourseInvitationTokenService $service;
    private ValidationTokenRepository $tokenRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = self::getContainer()->get(CourseInvitationTokenService::class);
        $this->tokenRepository = self::getContainer()->get(ValidationTokenRepository::class);
    }

    public function testCreateForCourseIsResolvableAndCourseOnly(): void
    {
        $course = $this->createCourse('Invitation course '.bin2hex(random_bytes(4)));
        $admin = $this->getAdmin();
        $email = 'invitee_'.bin2hex(random_bytes(4)).'@example.com';

        $created = $this->service->create($course, null, null, $email, $admin);

        $this->assertStringEndsWith('auth/registration.php?invitation='.$created['token']->getHash(), $created['url']);
        $this->assertSame($email, $created['invitation']->getEmail());
        $this->assertFalse($created['invitation']->isSessionInvitation());
        $this->assertFalse($created['invitation']->isAccepted());

        $resolved = $this->service->resolve($created['token']->getHash());

        $this->assertNotNull($resolved);
        $this->assertSame($created['invitation']->getId(), $resolved['invitation']->getId());
    }

    public function testCreateForSessionMarksInvitationAsSessionInvitation(): void
    {
        $course = $this->createCourse('Invitation session course '.bin2hex(random_bytes(4)));
        $session = $this->createSession('Invitation session '.bin2hex(random_bytes(4)));
        $admin = $this->getAdmin();
        $email = 'invitee_'.bin2hex(random_bytes(4)).'@example.com';

        $created = $this->service->create($course, $session, null, $email, $admin);

        $this->assertTrue($created['invitation']->isSessionInvitation());
        $this->assertSame($session->getId(), $created['invitation']->getSession()->getId());
        $this->assertSame($course->getId(), $created['invitation']->getCourse()->getId());
    }

    public function testCreateWithoutCourseOrSessionThrows(): void
    {
        $admin = $this->getAdmin();

        $this->expectException(InvalidArgumentException::class);
        $this->service->create(null, null, null, 'nobody@example.com', $admin);
    }

    public function testResolveReturnsNullForUnknownHash(): void
    {
        $this->assertNull($this->service->resolve('this-hash-does-not-exist'));
    }

    public function testResolveReturnsNullForExpiredToken(): void
    {
        $course = $this->createCourse('Invitation expiry course '.bin2hex(random_bytes(4)));
        $admin = $this->getAdmin();

        $created = $this->service->create($course, null, null, 'expired@example.com', $admin);

        $token = $created['token'];
        $token->setCreatedAt((new DateTime())->modify('-8 days'));
        $this->tokenRepository->save($token, true);

        $this->assertNull($this->service->resolve($token->getHash()));
    }

    public function testConfirmAcceptsInvitationAndConsumesTokenOnce(): void
    {
        $course = $this->createCourse('Invitation confirm course '.bin2hex(random_bytes(4)));
        $admin = $this->getAdmin();
        $registeredUser = $this->createUser('invitee_'.bin2hex(random_bytes(4)));

        $created = $this->service->create($course, null, null, 'confirm@example.com', $admin);
        $hash = $created['token']->getHash();

        $resolved = $this->service->resolve($hash);
        $this->assertNotNull($resolved);

        $this->service->confirm($resolved, $registeredUser);

        $this->assertTrue($created['invitation']->isAccepted());
        $this->assertSame($registeredUser->getId(), $created['invitation']->getRegisteredUser()->getId());

        // The token is deleted on confirm, so the same hash cannot be resolved a second time.
        $this->assertNull($this->service->resolve($hash));
        $this->assertNull($this->tokenRepository->findOneBy([
            'type' => ValidationTokenHelper::TYPE_COURSE_INVITATION,
            'hash' => $hash,
        ]));
    }

    public function testResolveRejectsInvitationIssuedFromADifferentAccessUrl(): void
    {
        $admin = $this->getAdmin();
        $course = $this->createCourse('Invitation other portal course '.bin2hex(random_bytes(4)));

        $accessUrlRepository = self::getContainer()->get(AccessUrlRepository::class);
        $otherAccessUrl = (new AccessUrl())
            ->setUrl('https://other-portal-'.bin2hex(random_bytes(4)).'.example.com/')
            ->setActive(1)
            ->setCreator($admin)
            ->setCreatedBy((int) $admin->getId())
            ->setDescription('Other portal used to test cross-URL invitation rejection')
            ->setTms(new DateTime())
        ;
        $accessUrlRepository->create($otherAccessUrl);

        // Bypass the service's create() (which always stamps the *current* access
        // URL) to simulate an invitation that was issued from a different portal.
        $invitation = new CourseInvitation('other-portal@example.com', $admin, $otherAccessUrl);
        $invitation->setCourse($course);
        self::getContainer()->get(CourseInvitationRepository::class)->save($invitation, true);

        $token = new ValidationToken(ValidationTokenHelper::TYPE_COURSE_INVITATION, (int) $invitation->getId());
        $this->tokenRepository->save($token, true);

        $this->assertNull($this->service->resolve($token->getHash()));
    }

    public function testRevokeInvalidatesTokenAndMarksInvitationRevoked(): void
    {
        $course = $this->createCourse('Invitation revoke course '.bin2hex(random_bytes(4)));
        $admin = $this->getAdmin();

        $created = $this->service->create($course, null, null, 'revoke@example.com', $admin);
        $hash = $created['token']->getHash();

        $this->service->revoke($created['invitation'], $admin);

        $this->assertTrue($created['invitation']->isRevoked());
        $this->assertNull($this->service->resolve($hash));
        $this->assertNull($this->tokenRepository->findOneBy([
            'type' => ValidationTokenHelper::TYPE_COURSE_INVITATION,
            'hash' => $hash,
        ]));
    }

    public function testRevokeRejectsAnAlreadyAcceptedInvitation(): void
    {
        $course = $this->createCourse('Invite revoke accepted '.bin2hex(random_bytes(4)));
        $admin = $this->getAdmin();
        $registeredUser = $this->createUser('invitee_'.bin2hex(random_bytes(4)));

        $created = $this->service->create($course, null, null, 'already-accepted@example.com', $admin);
        $resolved = $this->service->resolve($created['token']->getHash());
        $this->assertNotNull($resolved);
        $this->service->confirm($resolved, $registeredUser);

        $this->expectException(InvalidArgumentException::class);
        $this->service->revoke($created['invitation'], $admin);
    }

    public function testGetActiveUrlOnlyReturnsALinkForAPendingInvitation(): void
    {
        $course = $this->createCourse('Invite active url '.bin2hex(random_bytes(4)));
        $admin = $this->getAdmin();
        $registeredUser = $this->createUser('invitee_'.bin2hex(random_bytes(4)));

        $created = $this->service->create($course, null, null, 'active-url@example.com', $admin);

        $this->assertSame($created['url'], $this->service->getActiveUrl($created['invitation']));

        $resolved = $this->service->resolve($created['token']->getHash());
        $this->assertNotNull($resolved);
        $this->service->confirm($resolved, $registeredUser);

        // Once accepted, the ValidationToken is gone — nothing left to hand out.
        $this->assertNull($this->service->getActiveUrl($created['invitation']));
    }

    public function testGetActiveUrlReturnsNullAfterRevoke(): void
    {
        $course = $this->createCourse('Invite active url revoke '.bin2hex(random_bytes(4)));
        $admin = $this->getAdmin();

        $created = $this->service->create($course, null, null, 'revoked-url@example.com', $admin);
        $this->service->revoke($created['invitation'], $admin);

        $this->assertNull($this->service->getActiveUrl($created['invitation']));
    }
}
