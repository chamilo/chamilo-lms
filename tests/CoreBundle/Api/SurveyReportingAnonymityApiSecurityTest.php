<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression test for survey.survey_anonymous_show_answered on /api/survey/reporting/{surveyId}.
 *
 * With the setting off, the teacher of an anonymous survey must not learn which invitee
 * answered, nor when. The report used to send both and leave the decision to Vue, where
 * anyone reading the API response could ignore it.
 */
final class SurveyReportingAnonymityApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const string ANSWERED_AT = '2030-01-02 10:00:00';

    public function testAnonymousSurveyHidesWhoAnsweredWhenTheSettingIsOff(): void
    {
        $this->setShowAnswered(false);
        $data = $this->requestReport('1', 'off');

        foreach ($data['users'] as $user) {
            $this->assertFalse($user['answered'], 'The report must not reveal which invitee answered.');
            $this->assertNull($user['answeredAt'], 'The report must not reveal when an invitee answered.');
        }

        // A lone respondent among the invitees is identifiable from the total alone.
        $this->assertSame(0, $data['counts']['answered']);
    }

    public function testAnonymousSurveyShowsWhoAnsweredWhenTheSettingIsOn(): void
    {
        $this->setShowAnswered(true);

        $this->assertRealParticipation($this->requestReport('1', 'on'));
    }

    public function testNamedSurveyIgnoresTheSetting(): void
    {
        $this->setShowAnswered(false);

        $this->assertRealParticipation($this->requestReport('0', 'named'));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertRealParticipation(array $data): void
    {
        $participation = array_map(
            static fn (array $user): array => [$user['answered'], $user['answeredAt']],
            $data['users']
        );
        sort($participation);

        $this->assertSame([[false, null], [true, self::ANSWERED_AT]], $participation);
        $this->assertSame(1, $data['counts']['answered']);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestReport(string $anonymous, string $suffix): array
    {
        $course = $this->createCourse('srat course '.$suffix);
        $teacher = $this->createUser('srat_teacher_'.$suffix);
        $course->addUserAsTeacher($teacher);
        self::getContainer()->get(CourseRepository::class)->update($course);

        $survey = $this->createSurvey($course, $anonymous, $suffix);

        $response = $this
            ->createClientWithCredentials($this->getUserTokenFromUser($teacher))
            ->request('GET', '/api/survey/reporting/'.$survey->getIid().'?cid='.$course->getId())
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        return $response->toArray();
    }

    /**
     * Two invitees: the first answered, the second did not.
     */
    private function createSurvey(Course $course, string $anonymous, string $suffix): CSurvey
    {
        $em = $this->getEntityManager();

        $survey = (new CSurvey())
            ->setCode('srat_'.$suffix)
            ->setTitle('Reporting anonymity')
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
        $em->persist($survey);

        foreach ([true, false] as $index => $answered) {
            /** @var User $student */
            $student = $this->createUser('srat_'.$suffix.'_'.$index);
            $invitation = (new CSurveyInvitation())
                ->setSurvey($survey)
                ->setCourse($course)
                ->setUser($student)
                ->setInvitationCode('srat-'.$suffix.'-'.$index)
                ->setAnswered($answered ? 1 : 0)
            ;
            if ($answered) {
                $invitation->setAnsweredAt(new DateTime(self::ANSWERED_AT));
            }
            $em->persist($invitation);
        }
        $em->flush();

        return $survey;
    }

    private function setShowAnswered(bool $enabled): void
    {
        self::getContainer()->get(SettingsManager::class)
            ->updateSetting('survey.survey_anonymous_show_answered', $enabled ? 'true' : 'false')
        ;
    }
}
