<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Command\SendSurveyInvitationsCommand;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Service\Survey\SurveyInvitationDelivery;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAutoInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class SurveyInvitationDeliveryApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testExternalAddressNormalization(): void
    {
        self::assertSame(['qa@example.invalid'], SurveyInvitationDelivery::normalizeExternalEmails([' QA@example.invalid ', 'qa@example.invalid']));
        $this->expectException(BadRequestHttpException::class);
        SurveyInvitationDelivery::normalizeExternalEmails(['invalid']);
    }

    public function testExternalPublicationAndSingleUseAnonymousAnswers(): void
    {
        $settings = self::getContainer()->get(SettingsManager::class);
        self::assertContains((string) $settings->getSetting('mail.mailer_dsn', true), ['', 'null://null']);
        self::assertContains((string) $settings->getSetting('mail.mailer_dsn_massmailing', true), ['', 'null://null']);
        $originalSender = $settings->getSetting('mail.mailer_from_email', true);
        $settings->updateSetting('mail.mailer_from_email', 'survey-qa@example.invalid');

        try {
            $suffix = bin2hex(random_bytes(4));
            $course = $this->createCourse('External survey '.$suffix);
            $teacher = $this->createUser('survey_ext_teacher_'.$suffix, role: 'ROLE_TEACHER');
            $course->addUserAsTeacher($teacher);
            self::getContainer()->get(CourseRepository::class)->update($course);
            $survey = $this->createDeliverySurvey($course, $suffix, '1');
            $named = $this->createDeliverySurvey($course, $suffix.'_named');
            $question = (new CSurveyQuestion())->setSurvey($survey)->setSurveyQuestion('Your feedback')->setSurveyQuestionComment('')->setType('open')->setDisplay('vertical')->setSort(1);
            $em = $this->getEntityManager();
            $em->persist($question);
            $em->flush();
            $surveyId = (int) $survey->getIid();
            $namedId = (int) $named->getIid();
            $courseId = (int) $course->getId();
            $questionId = (int) $question->getIid();
            $client = $this->createClientWithCredentials($this->getUserTokenFromUser($teacher));
            $query = '?cid='.$courseId.'&sid=0';
            $url = '/api/survey/invitations/'.$surveyId.'/publish'.$query;
            $payload = ['selectedUserIds' => [], 'sendMail' => true, 'mailSubject' => 'External QA', 'mailText' => 'Answer **link**', 'additionalEmails' => ['QA@example.invalid', 'qa@example.invalid', 'other@example.invalid']];
            $client->request('POST', '/api/survey/invitations/'.$namedId.'/publish'.$query, ['json' => $payload]);
            self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
            $result = $client->request('POST', $url, ['json' => $payload])->toArray();
            self::assertResponseIsSuccessful();
            self::assertSame(2, $result['counts']['invited']);
            self::assertSame(0, $result['failedExternalInvitations']);
            self::assertSame(2, $result['history'][0]['recipientCount']);
            foreach ($result['invitations'] as $invitation) {
                self::assertTrue($invitation['external']);
                self::assertArrayNotHasKey('invitationCode', $invitation);
                self::assertArrayNotHasKey('answerUrl', $invitation);
            }
            $again = $client->request('POST', $url, ['json' => $payload])->toArray();
            self::assertResponseIsSuccessful();
            self::assertSame(2, $again['counts']['invited']);
            self::assertCount(1, $again['history']);
            $db = $this->getEntityManager()->getConnection();
            $codes = $db->fetchFirstColumn('SELECT i.invitation_code FROM c_survey_invitation i JOIN c_survey_external_invitation e ON e.invitation_id = i.iid WHERE i.survey_id = ? ORDER BY e.id', [$surveyId]);
            self::assertCount(2, $codes);
            $guest = self::createClient([], ['headers' => ['sec-fetch-site' => 'same-origin']]);
            foreach ($codes as $index => $code) {
                $answerUrl = '/api/survey/answer/'.$surveyId.'?publicCid='.$courseId.'&publicSid=0&invitationCode='.rawurlencode($code);
                $answer = $guest->request('GET', $answerUrl)->toArray();
                self::assertResponseIsSuccessful();
                self::assertTrue($answer['canSubmit']);
                $response = $guest->request('POST', $answerUrl, ['json' => ['answers' => [(string) $questionId => 'QA response '.$index]]])->toArray();
                self::assertResponseIsSuccessful();
                self::assertFalse($response['canSubmit']);
                self::assertSame([], $response['answers']);
                $guest->request('POST', $answerUrl, ['json' => ['answers' => [(string) $questionId => 'Replay']]]);
                self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
            }
            $db = $this->getEntityManager()->getConnection();
            $keys = $db->fetchFirstColumn('SELECT DISTINCT user FROM c_survey_answer WHERE survey_id = ?', [$surveyId]);
            self::assertCount(2, $keys);
            foreach ($keys as $key) {
                self::assertStringStartsWith('surveyuser_', $key);
                self::assertNotContains($key, $codes);
            }
        } finally {
            self::getContainer()->get(SettingsManager::class)->updateSetting('mail.mailer_from_email', $originalSender ?? '');
        }
    }

    public function testAutomaticPolicyOnlySelectsNewSubscriptions(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $course = $this->createCourse('Automatic survey '.$suffix);
        $existing = $this->createUser('survey_auto_old_'.$suffix, role: 'ROLE_STUDENT');
        $new = $this->createUser('survey_auto_new_'.$suffix, email: 'survey-auto-'.$suffix.'@example.invalid', role: 'ROLE_STUDENT');
        $repo = self::getContainer()->get(CourseRepository::class);
        $course->addUserAsStudent($existing);
        $repo->update($course);
        $survey = $this->createDeliverySurvey($course, $suffix);
        $portal = $this->getAccessUrl();
        $delivery = self::getContainer()->get(SurveyInvitationDelivery::class);
        $delivery->configurePolicy($survey, $course, null, $portal, true, true, false, false, 'Initial message', 'Please answer **link**');
        $em = $this->getEntityManager();
        $em->flush();
        $policy = $delivery->getPolicy($survey, $course, null, $portal);
        self::assertInstanceOf(CSurveyAutoInvitation::class, $policy);
        self::assertSame([], $delivery->pendingUsers($policy));
        $course->addUserAsStudent($new);
        $repo->update($course);
        $users = $delivery->pendingUsers($policy);
        self::assertCount(1, $users);
        self::assertSame($new->getId(), $users[0]->getId());
        $delivery->configurePolicy($survey, $course, null, $portal, true, true, false, true, 'Reminder', 'Reminder text');
        self::assertSame('Initial message', $policy->subject);
        self::assertSame(['pending' => 1, 'sent' => 0, 'failed' => 0], $delivery->processPolicy($policy, false));
        self::assertSame(0, (int) $em->getConnection()->fetchOne('SELECT COUNT(*) FROM c_survey_invitation WHERE survey_id = ?', [$survey->getIid()]));
        $invitation = $delivery->createInvitation($survey, $course, null, $new);
        $em->flush();
        self::assertNotNull($invitation->getIid());
        self::assertSame([], $delivery->pendingUsers($policy));
        $delivery->configurePolicy($survey, $course, null, $portal, false, false, false, false, '', '');
        $em->flush();
        self::assertNull($delivery->getPolicy($survey, $course, null, $portal));
    }

    public function testScheduledSessionDeliveryWithNullTransport(): void
    {
        $em = $this->getEntityManager();
        $db = $em->getConnection();
        self::assertSame('chamilo_test_test', $db->fetchOne('SELECT DATABASE()'));
        $suffix = bin2hex(random_bytes(4));
        $course = $this->createCourse('Scheduled survey '.$suffix);
        $portal = $this->getAccessUrl();
        $session = $this->createSession('Scheduled session '.$suffix);
        $otherSession = $this->createSession('Unrelated session '.$suffix);
        foreach ([$session, $otherSession] as $context) {
            $context->addCourse($course)
                ->setAccessStartDate(new DateTime('-1 day'))
                ->setAccessEndDate(new DateTime('+1 day'))
            ;
        }
        $old = $this->createUser('survey_scheduled_old_'.$suffix, email: 'old-'.$suffix.'@example.invalid', role: 'ROLE_STUDENT');
        $new = $this->createUser('survey_scheduled_new_'.$suffix, email: 'new-'.$suffix.'@example.invalid', role: 'ROLE_STUDENT');
        $other = $this->createUser('survey_scheduled_other_'.$suffix, email: 'other-'.$suffix.'@example.invalid', role: 'ROLE_STUDENT');
        $session->addUserInSession(Session::STUDENT, $old);
        $session->addUserInCourse(Session::STUDENT, $old, $course);
        $em->flush();
        $survey = $this->createDeliverySurvey($course, $suffix);
        $survey->addCourseLink($course, $session);
        $em->flush();
        $delivery = self::getContainer()->get(SurveyInvitationDelivery::class);
        self::assertFalse($delivery->supportsAutomatic($survey, $course, $otherSession, $portal));
        $delivery->configurePolicy($survey, $course, $session, $portal, true, true, false, false, 'Scheduled QA', 'Please answer **link**');
        $em->flush();
        $policy = $delivery->getPolicy($survey, $course, $session, $portal);
        self::assertInstanceOf(CSurveyAutoInvitation::class, $policy);
        self::assertSame([], $delivery->pendingUsers($policy));
        $session->addUserInSession(Session::STUDENT, $new);
        $session->addUserInCourse(Session::STUDENT, $new, $course);
        $otherSession->addUserInSession(Session::STUDENT, $other);
        $otherSession->addUserInCourse(Session::STUDENT, $other, $course);
        $em->flush();
        $pending = $delivery->pendingUsers($policy);
        self::assertCount(1, $pending);
        self::assertSame($new->getId(), $pending[0]->getId());

        $settings = self::getContainer()->get(SettingsManager::class);
        $settings->setUrl($portal);
        $originalDsn = $settings->getSetting('mail.mailer_dsn', true);
        $originalSender = $settings->getSetting('mail.mailer_from_email', true);
        self::assertContains((string) $originalDsn, ['', 'null://null']);

        try {
            $settings->updateSetting('mail.mailer_dsn', 'null://null');
            $settings->updateSetting('mail.mailer_from_email', 'survey-qa@example.invalid');
            self::assertSame('null://null', $settings->getSetting('mail.mailer_dsn', true));
            $command = new CommandTester(new SendSurveyInvitationsCommand($em, $delivery));
            $options = ['--survey-id' => (string) $survey->getIid()];
            self::assertSame(0, $command->execute($options));
            self::assertStringContainsString('pending=1 sent=0 failed=0', $command->getDisplay());
            self::assertSame(0, (int) $db->fetchOne('SELECT COUNT(*) FROM c_survey_invitation WHERE survey_id = ?', [$survey->getIid()]));
            self::assertSame(0, $command->execute($options + ['--send' => true]));
            self::assertStringContainsString('pending=1 sent=1 failed=0', $command->getDisplay());
            self::assertSame(1, (int) $db->fetchOne('SELECT COUNT(*) FROM c_survey_invitation WHERE survey_id = ?', [$survey->getIid()]));
            self::assertSame((int) $new->getId(), (int) $db->fetchOne('SELECT user_id FROM c_survey_invitation WHERE survey_id = ? AND session_id = ?', [$survey->getIid(), $session->getId()]));
            self::assertSame(1, (int) $db->fetchOne('SELECT COUNT(*) FROM c_survey_invitation_history WHERE survey_id = ? AND session_id = ? AND recipient_count = 1', [$survey->getIid(), $session->getId()]));
            self::assertSame(1, (int) $db->fetchOne('SELECT COUNT(*) FROM c_survey_invitation_history_rel_invitation r JOIN c_survey_invitation_history h ON h.iid = r.history_id WHERE h.survey_id = ?', [$survey->getIid()]));
            self::assertSame(0, $command->execute($options + ['--send' => true]));
            self::assertStringContainsString('pending=0 sent=0 failed=0', $command->getDisplay());
            self::assertSame(1, (int) $db->fetchOne('SELECT COUNT(*) FROM c_survey_invitation_history WHERE survey_id = ?', [$survey->getIid()]));
        } finally {
            $settings->updateSetting('mail.mailer_dsn', $originalDsn ?? '');
            $settings->updateSetting('mail.mailer_from_email', $originalSender ?? '');
        }
    }

    private function createDeliverySurvey(Course $course, string $suffix, string $anonymous = '0'): CSurvey
    {
        $survey = (new CSurvey())
            ->setCode('delivery_'.$suffix)
            ->setTitle('Invitation delivery QA')
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
