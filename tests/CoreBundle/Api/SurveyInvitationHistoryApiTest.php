<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyInvitationHistory;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

/** History must survive a reminder without replacing the original dispatch. */
final class SurveyInvitationHistoryApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testPublicationAndRemindersKeepIndependentHistory(): void
    {
        // The test transport is null://null; do not run with a real mail transport.
        $settings = self::getContainer()->get(SettingsManager::class);
        self::assertContains((string) $settings->getSetting('mail.mailer_dsn', true), ['', 'null://null']);
        self::assertContains((string) $settings->getSetting('mail.mailer_dsn_massmailing', true), ['', 'null://null']);

        $suffix = bin2hex(random_bytes(4));
        $course = $this->createCourse('Survey history '.$suffix);
        $teacher = $this->createUser('survey_hist_teacher_'.$suffix, role: 'ROLE_TEACHER');
        $student1 = $this->createUser('survey_hist_one_'.$suffix, email: 'survey-one-'.$suffix.'@example.invalid', role: 'ROLE_STUDENT');
        $student2 = $this->createUser('survey_hist_two_'.$suffix, email: 'survey-two-'.$suffix.'@example.invalid', role: 'ROLE_STUDENT');
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student1);
        $course->addUserAsStudent($student2);
        self::getContainer()->get(CourseRepository::class)->update($course);
        $survey = $this->createHistorySurvey($course, $suffix);
        $surveyId = (int) $survey->getIid();
        $courseId = (int) $course->getId();
        $studentIds = [(int) $student1->getId(), (int) $student2->getId()];
        $studentToken = $this->getUserTokenFromUser($student1);
        $teacherToken = $this->getUserTokenFromUser($teacher);
        $client = $this->createClientWithCredentials($teacherToken);
        $url = '/api/survey/invitations/'.$surveyId;
        $query = '?cid='.$courseId.'&sid=0';

        $initial = $client->request('GET', $url.$query)->toArray();
        self::assertResponseIsSuccessful();
        self::assertEqualsCanonicalizing($studentIds, $initial['selectedUserIds']);
        self::assertSame([], $initial['history']);
        self::assertSame(0, $initial['counts']['invited']);

        $payload = [
            'selectedUserIds' => [$studentIds[0]],
            'mailSubject' => 'First survey invitation',
            'mailText' => 'Please answer **link**',
            'sendMail' => false,
        ];
        $result = $client->request('POST', $url.'/publish'.$query, ['json' => $payload])->toArray();
        self::assertResponseIsSuccessful();
        self::assertSame([], $result['history']);
        self::assertSame(1, $result['counts']['invited']);
        self::assertSame([$studentIds[0]], $result['selectedUserIds']);

        $payload['selectedUserIds'] = $studentIds;
        $payload['sendMail'] = true;
        $payload['resendToAll'] = true;
        $result = $client->request('POST', $url.'/publish'.$query, ['json' => $payload])->toArray();
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result['history']);
        $first = $result['history'][0];
        self::assertSame(2, $first['recipientCount']);
        self::assertSame('First survey invitation', $first['subject']);
        self::assertSame('Please answer **link**', $first['body']);
        self::assertArrayNotHasKey('invitations', $first);
        self::assertSame(2, $result['counts']['invited']);

        $db = $this->getEntityManager()->getConnection();
        self::assertSame(2, (int) $db->fetchOne('SELECT COUNT(*) FROM c_survey_invitation_history_rel_invitation WHERE history_id = ?', [$first['id']]));
        $db->executeStatement('UPDATE c_survey_invitation SET answered = 1 WHERE survey_id = ? AND user_id = ?', [$surveyId, $studentIds[0]]);
        $payload['mailSubject'] = 'Reminder';
        $payload['mailText'] = 'Please complete the survey **link**';
        $payload['resendToAll'] = false;
        $payload['remindUnanswered'] = true;
        $result = $client->request('POST', $url.'/publish'.$query, ['json' => $payload])->toArray();
        self::assertResponseIsSuccessful();
        self::assertCount(2, $result['history']);
        self::assertSame(1, $result['history'][0]['recipientCount']);
        self::assertSame('Reminder', $result['history'][0]['subject']);
        self::assertSame($first, $result['history'][1]);
        $db = $this->getEntityManager()->getConnection();
        self::assertSame($studentIds[1], (int) $db->fetchOne('SELECT i.user_id FROM c_survey_invitation_history_rel_invitation r JOIN c_survey_invitation i ON i.iid = r.invitation_id WHERE r.history_id = ?', [$result['history'][0]['id']]));

        $payload['remindUnanswered'] = false;
        $result = $client->request('POST', $url.'/publish'.$query, ['json' => $payload])->toArray();
        self::assertResponseIsSuccessful();
        self::assertCount(2, $result['history'], 'Publishing again without a resend must not create another dispatch.');

        $studentClient = $this->createClientWithCredentials($studentToken);
        $studentClient->request('GET', $url.$query);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testHistoryIsScopedAndDoesNotRevealRestrictedReminders(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $course = $this->createCourse('Anonymous history '.$suffix);
        $otherCourse = $this->createCourse('Unrelated history '.$suffix);
        $teacher = $this->createUser('survey_hist_anon_teacher_'.$suffix, role: 'ROLE_TEACHER');
        $student = $this->createUser('survey_hist_anon_student_'.$suffix, role: 'ROLE_STUDENT');
        $course->addUserAsTeacher($teacher);
        $otherCourse->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $repo = self::getContainer()->get(CourseRepository::class);
        $repo->update($course);
        $repo->update($otherCourse);
        $survey = $this->createHistorySurvey($course, $suffix, '1');
        $session = $this->createSession('History session '.$suffix);
        $em = $this->getEntityManager();
        $invitation = (new CSurveyInvitation())->setSurvey($survey)->setCourse($course)->setUser($student)->setInvitationCode(bin2hex(random_bytes(16)));
        $sessionInvitation = (new CSurveyInvitation())->setSurvey($survey)->setCourse($course)->setSession($session)->setUser($student)->setInvitationCode(bin2hex(random_bytes(16)));
        $em->persist($invitation);
        $em->persist($sessionInvitation);
        for ($i = 1; $i <= 21; ++$i) {
            $batch = new CSurveyInvitationHistory($survey, $course, null, 'Batch '.$i, 'Template', false, false);
            $batch->addInvitation($invitation);
            $em->persist($batch);
        }
        $restricted = new CSurveyInvitationHistory($survey, $course, null, 'Restricted reminder', 'Template', true, false);
        $restricted->addInvitation($invitation);
        $em->persist($restricted);
        $sessionBatch = new CSurveyInvitationHistory($survey, $course, $session, 'Session only', 'Template', false, false);
        $sessionBatch->addInvitation($sessionInvitation);
        $em->persist($sessionBatch);
        $em->flush();
        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->updateSetting('survey.survey_anonymous_show_answered', 'false');
        $url = '/api/survey/invitations/'.$survey->getIid();
        $query = '?cid='.$course->getId().'&sid=0';
        $wrongQuery = '?cid='.$otherCourse->getId().'&sid=0';
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($teacher));
        $firstPage = $client->request('GET', $url.$query)->toArray();
        self::assertResponseIsSuccessful();
        self::assertCount(20, $firstPage['history']);
        self::assertTrue($firstPage['historyHasMore']);
        self::assertSame('Batch 21', $firstPage['history'][0]['subject']);
        $secondPage = $client->request('GET', $url.$query.'&historyPage=2')->toArray();
        self::assertResponseIsSuccessful();
        self::assertCount(1, $secondPage['history']);
        self::assertFalse($secondPage['historyHasMore']);
        self::assertSame('Batch 1', $secondPage['history'][0]['subject']);
        $client->request('GET', $url.$wrongQuery);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function createHistorySurvey(Course $course, string $suffix, string $anonymous = '0'): CSurvey
    {
        $survey = (new CSurvey())
            ->setCode('history_'.$suffix)
            ->setTitle('Invitation history QA')
            ->setLang('en_US')
            ->setAvailFrom(new DateTime('-1 day'))
            ->setAvailTill(new DateTime('+1 day'))
            ->setSurveythanks('')
            ->setAnonymous($anonymous)
            ->setVisibleResults(0)
            ->setIsShared('0')
            ->setTemplate('template')
            ->setSurveyType(0)
            ->setSurveyVersion('')
            ->setParent($course)
            ->setCreator($this->getUser('admin'))
            ->addCourseLink($course)
        ;
        $em = $this->getEntityManager();
        $em->persist($survey);
        $em->flush();

        return $survey;
    }
}
