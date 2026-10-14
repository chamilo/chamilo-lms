<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\CatalogueSessionRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * A learner may subscribe themselves from the session catalogue only to what the catalogue
 * offers them. The catalogue applies its rules (sale, selection, prerequisites) in the browser,
 * so the API has to apply them again: the requests below are exactly the ones its Subscribe
 * button sends, aimed at a session the catalogue does not list.
 */
final class SessionSelfSubscriptionApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private function setAutoSubscription(string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting(
            'catalog.allow_session_auto_subscription',
            $value
        );
    }

    /**
     * Once the access URL lists some sessions, only those are in the catalogue.
     */
    private function listOnlyInCatalogue(Session $session): void
    {
        $em = $this->getEntityManager();
        $em->persist(
            (new CatalogueSessionRelAccessUrlRelUsergroup())
                ->setSession($session)
                ->setAccessUrl($this->getAccessUrl())
        );
        $em->flush();
    }

    private function subscribeToSession(User $user, Session $session): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request('POST', '/api/session_rel_users', [
            'json' => [
                'user' => '/api/users/'.$user->getId(),
                'session' => '/api/sessions/'.$session->getId(),
                'relationType' => Session::STUDENT,
                'duration' => 0,
            ],
        ]);
    }

    private function subscribeToSessionCourse(User $user, Session $session, Course $course): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request('POST', '/api/session_rel_course_rel_users', [
            'json' => [
                'user' => '/api/users/'.$user->getId(),
                'session' => '/api/sessions/'.$session->getId(),
                'course' => '/api/courses/'.$course->getId(),
                'status' => Session::STUDENT,
                'visibility' => 1,
                'legalAgreement' => 0,
                'progress' => 0,
            ],
        ]);
    }

    public function testLearnerCanSubscribeToSessionOfTheCatalogue(): void
    {
        $this->setAutoSubscription('true');
        $learner = $this->createUser('sss_learner_ok');
        $session = $this->createSession('SSS catalogue');
        $course = $this->createCourse('SSSOK');

        $this->subscribeToSession($learner, $session);
        $this->assertResponseStatusCodeSame(201);

        $this->subscribeToSessionCourse($learner, $session, $course);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testLearnerCannotSubscribeToSessionOutsideTheCatalogue(): void
    {
        $this->setAutoSubscription('true');
        $learner = $this->createUser('sss_learner_out');
        $listed = $this->createSession('SSS listed');
        $hidden = $this->createSession('SSS hidden');
        $this->listOnlyInCatalogue($listed);

        $this->subscribeToSession($learner, $hidden);
        $this->assertResponseStatusCodeSame(403);

        $this->subscribeToSession($learner, $listed);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testLearnerCannotSubscribeToCourseOfSessionOutsideTheCatalogue(): void
    {
        $this->setAutoSubscription('true');
        $learner = $this->createUser('sss_learner_crs');
        $listed = $this->createSession('SSS listed course');
        $hidden = $this->createSession('SSS hidden course');
        $course = $this->createCourse('SSSCRS');
        $this->listOnlyInCatalogue($listed);

        $this->subscribeToSessionCourse($learner, $hidden, $course);
        $this->assertResponseStatusCodeSame(403);

        $this->subscribeToSessionCourse($learner, $listed, $course);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testLearnerCannotSubscribeWhenAutoSubscriptionIsOff(): void
    {
        $this->setAutoSubscription('false');
        $learner = $this->createUser('sss_learner_off');
        $session = $this->createSession('SSS off');

        $this->subscribeToSession($learner, $session);
        $this->assertResponseStatusCodeSame(403);
    }
}
