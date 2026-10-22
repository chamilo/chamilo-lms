<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Transcript;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * The transcript lists the courses a learner completed with their grades, and an official
 * transcript is a signed copy of it that third parties verify. So a learner reads only their own
 * transcript and never issues it: only an administrator, an HR manager following the learner or a
 * session administrator of the learner's session issues and revokes it. The verification page
 * must tell a valid transcript from a revoked one, and stop disclosing it once revoked.
 */
final class TranscriptApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testLearnerReadsTheirTranscriptWithGradesAndGpa(): void
    {
        $ctx = $this->bootstrapScenario('grades');

        $data = $this->getOverview($ctx['learner']);

        $this->assertFalse($data['canIssue']);
        $courses = $data['transcript']['courses'];
        $this->assertSame(['TRGRADES1', 'TRGRADES2'], array_column($courses, 'code'));
        $this->assertSame(['A', 'C'], array_column($courses, 'letter'));
        $this->assertEquals([10, 2.5], array_column($courses, 'requiredHours'));
        // By default, the hours completed are the time the learner spent in each course.
        $this->assertEquals([1.5, 0], array_column($courses, 'completedHours'));
        $this->assertSame(84, $data['transcript']['finalGrade']);
        $this->assertEquals(3.0, $data['transcript']['gpa']);
        $this->assertSame('Fire Science', $data['transcript']['programTitle']);
    }

    public function testCompletedHoursCanBeTheRequiredHours(): void
    {
        $this->updateSetting('certificate.transcript_completed_hours_are_required_hours', 'true');
        $ctx = $this->bootstrapScenario('requiredhours');

        $courses = $this->getOverview($ctx['learner'])['transcript']['courses'];

        $this->assertEquals([10, 2.5], array_column($courses, 'completedHours'));
    }

    public function testCourseCodeColumnCanShowTheDepartmentName(): void
    {
        $this->updateSetting('certificate.transcript_course_code_source', 'department_name');
        $ctx = $this->bootstrapScenario('department');

        $codes = array_column($this->getOverview($ctx['learner'])['transcript']['courses'], 'code');

        $this->assertSame(['RRdepartment1', 'RRdepartment2'], $codes);
    }

    public function testLearnerCannotReadOrIssueTheTranscriptOfSomeoneElse(): void
    {
        $ctx = $this->bootstrapScenario('learner');
        $classmate = $this->createUser('transcript_classmate_learner');

        $this->request($classmate, 'GET', '/api/transcripts/overview?userId='.$ctx['learner']->getId());
        $this->assertResponseStatusCodeSame(403);

        $this->request($ctx['learner'], 'POST', '/api/transcripts', ['userId' => $ctx['learner']->getId()]);
        $this->assertResponseStatusCodeSame(403);

        $this->request($classmate, 'POST', '/api/transcripts', ['userId' => $ctx['learner']->getId()]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testHrManagerIssuesOnlyForTheUsersTheyFollow(): void
    {
        $ctx = $this->bootstrapScenario('hrm');
        $userRepo = self::getContainer()->get(UserRepository::class);
        $followingHrm = $this->createUser('transcript_hrm_following', '', '', 'ROLE_HR');
        $otherHrm = $this->createUser('transcript_hrm_other', '', '', 'ROLE_HR');
        $learner = $userRepo->find($ctx['learner']->getId());
        $learner->addUserRelUser($followingHrm, UserRelUser::USER_RELATION_TYPE_HRM);
        $userRepo->update($learner);

        $this->request($otherHrm, 'POST', '/api/transcripts', ['userId' => $ctx['learner']->getId()]);
        $this->assertResponseStatusCodeSame(403);

        $this->request($followingHrm, 'POST', '/api/transcripts', ['userId' => $ctx['learner']->getId()]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testSessionAdminIssuesOnlyForTheLearnersOfTheirSessions(): void
    {
        $ctx = $this->bootstrapScenario('sessionadmin');
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $sessionAdmin = $this->createUser('transcript_session_admin', '', '', 'ROLE_SESSION_MANAGER');
        $otherSessionAdmin = $this->createUser('transcript_other_session_admin', '', '', 'ROLE_SESSION_MANAGER');

        $session = $this->createSession('Transcript session sessionadmin');
        $session->addSessionAdmin($sessionAdmin);
        $session->addUserInSession(Session::STUDENT, $this->getEntityManager()->getReference(User::class, $ctx['learner']->getId()));
        $sessionRepo->update($session);

        $this->request($otherSessionAdmin, 'POST', '/api/transcripts', ['userId' => $ctx['learner']->getId()]);
        $this->assertResponseStatusCodeSame(403);

        $this->request($sessionAdmin, 'POST', '/api/transcripts', ['userId' => $ctx['learner']->getId()]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testOfficialTranscriptIsVerifiableUntilRevoked(): void
    {
        $ctx = $this->bootstrapScenario('official');

        $issued = $this->request($ctx['admin'], 'POST', '/api/transcripts', ['userId' => $ctx['learner']->getId()]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('valid', $issued['status']);

        $transcript = $this->getEntityManager()->getRepository(Transcript::class)->find($issued['id']);
        $token = $transcript->getToken();

        $verification = $this->request(null, 'GET', '/transcripts/verify/'.$token.'.json');
        $this->assertResponseIsSuccessful();
        $this->assertTrue($verification['valid']);
        $this->assertSame(['A', 'C'], array_column($verification['transcript']['courses'], 'letter'));

        // The learner sees the official transcript issued for them, but cannot revoke it.
        $official = $this->getOverview($ctx['learner'])['officialTranscripts'];
        $this->assertCount(1, $official);
        $this->assertFalse($official[0]['canRevoke']);
        $this->request($ctx['learner'], 'POST', '/api/transcripts/'.$issued['id'].'/revoke');
        $this->assertResponseStatusCodeSame(403);

        $this->request($ctx['admin'], 'POST', '/api/transcripts/'.$issued['id'].'/revoke');
        $this->assertResponseIsSuccessful();

        $verification = $this->request(null, 'GET', '/transcripts/verify/'.$token.'.json');
        $this->assertSame('revoked', $verification['status']);
        $this->assertFalse($verification['valid']);
        $this->assertArrayNotHasKey('payloadJson', $verification);
    }

    public function testAlteredOfficialTranscriptFailsVerification(): void
    {
        $ctx = $this->bootstrapScenario('altered');
        $issued = $this->request($ctx['admin'], 'POST', '/api/transcripts', ['userId' => $ctx['learner']->getId()]);
        $this->assertResponseStatusCodeSame(201);

        $connection = $this->getEntityManager()->getConnection();
        $connection->executeStatement(
            "UPDATE transcript SET payload_json = REPLACE(payload_json, '\"letter\":\"C\"', '\"letter\":\"A\"') WHERE id = ?",
            [$issued['id']],
        );
        $token = $connection->fetchOne('SELECT token FROM transcript WHERE id = ?', [$issued['id']]);

        $verification = $this->request(null, 'GET', '/transcripts/verify/'.$token.'.json');
        $this->assertSame('invalid_signature', $verification['status']);
        $this->assertArrayNotHasKey('transcript', $verification);
    }

    public function testOfficialPdfIsOnlyForTheLearnerAndTheirManagers(): void
    {
        $ctx = $this->bootstrapScenario('pdf');
        $issued = $this->request($ctx['admin'], 'POST', '/api/transcripts', ['userId' => $ctx['learner']->getId()]);
        $classmate = $this->createUser('transcript_classmate_pdf');

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['learner']));
        $response = $client->request('GET', $issued['downloadUrl']);
        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->createClientWithCredentials($this->getUserTokenFromUser($classmate))->request('GET', $issued['downloadUrl']);
        $this->assertResponseStatusCodeSame(403);

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['learner']));
        $response = $client->request('GET', '/api/transcripts/users/'.$ctx['learner']->getId().'/unofficial.pdf');
        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function testTranscriptsAreUnavailableWhenDisabled(): void
    {
        $ctx = $this->bootstrapScenario('disabled');
        $this->updateSetting('certificate.transcript_enabled', 'false');

        $this->request($ctx['learner'], 'GET', '/api/transcripts/overview');
        $this->assertResponseStatusCodeSame(404);
    }

    private function updateSetting(string $name, string $value): void
    {
        self::getContainer()->get(SettingsManager::class)->updateSetting($name, $value);
    }

    /**
     * @return array<string, mixed>
     */
    private function getOverview(User $user): array
    {
        $data = $this->request($user, 'GET', '/api/transcripts/overview');
        $this->assertResponseIsSuccessful();

        return $data;
    }

    /**
     * @param array<string, mixed>|null $json
     *
     * @return array<string, mixed>
     */
    private function request(?User $user, string $method, string $uri, ?array $json = null): array
    {
        $client = null === $user
            ? static::createClient()
            : $this->createClientWithCredentials($this->getUserTokenFromUser($user));
        $response = $client->request($method, $uri, null === $json ? [] : ['json' => $json]);

        return $response->getStatusCode() < 300 || 404 === $response->getStatusCode()
            ? (json_decode($response->getContent(false), true) ?? [])
            : [];
    }

    /**
     * A learner who got a certificate in two courses of 10 and 2.5 hours, with 92.4% (A) and 75% (C),
     * after spending 1.5 hours in the first one and no tracked time in the second one.
     *
     * @return array{admin: User, learner: User}
     */
    private function bootstrapScenario(string $suffix): array
    {
        $this->updateSetting('certificate.transcript_enabled', 'true');
        $this->updateSetting('certificate.transcript_program_title', 'Fire Science');

        $admin = $this->createUser('transcript_admin_'.$suffix, '', '', 'ROLE_ADMIN');
        $learner = $this->createUser('transcript_learner_'.$suffix);
        $course = $this->addCertificate($learner, strtoupper('TR'.$suffix).'1', 'RR'.$suffix.'1', 36000, 92.4, '2026-01-15');
        $this->addCourseAccess($learner, $course, '2026-01-10 09:00:00', '2026-01-10 10:00:00');
        $this->addCourseAccess($learner, $course, '2026-01-12 14:00:00', '2026-01-12 14:30:00');
        $this->addCertificate($learner, strtoupper('TR'.$suffix).'2', 'RR'.$suffix.'2', 9000, 75.0, '2026-03-20');

        return ['admin' => $admin, 'learner' => $learner];
    }

    private function addCourseAccess(User $learner, Course $course, string $login, string $logout): void
    {
        $this->getEntityManager()->getConnection()->insert('track_e_course_access', [
            'user_id' => $learner->getId(),
            'c_id' => $course->getId(),
            'login_course_date' => $login,
            'logout_course_date' => $logout,
            'counter' => 1,
            'session_id' => 0,
            'user_ip' => '127.0.0.1',
        ]);
    }

    private function addCertificate(User $learner, string $code, string $department, int $durationInSeconds, float $score, string $date): Course
    {
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $em = $this->getEntityManager();

        $course = $this->createCourse('Transcript course '.$code);
        $course->setVisualCode($code)->setDepartmentName($department)->setDuration($durationInSeconds);
        $course->addUserAsStudent($learner);
        $courseRepo->update($course);

        $category = (new GradebookCategory())
            ->setTitle($code)
            ->setCourse($course)
            ->setWeight(100)
            ->setVisible(true)
            ->setLocked(0)
            ->setGenerateCertificates(true)
        ;
        $em->persist($category);

        $certificate = (new GradebookCertificate())
            ->setCategory($category)
            ->setUser($learner)
            ->setScoreCertificate($score)
            ->setCreator($learner)
        ;
        $em->persist($certificate);
        $em->flush();

        // created_at is Timestampable: set the completion date once the row exists.
        $certificate->setCreatedAt(new DateTime($date));
        $em->flush();

        return $course;
    }
}
