<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regression test for the anonymity of meeting polls on /api/survey/meeting/{surveyId}.
 *
 * Before the fix, an anonymous meeting poll only replaced the participant name
 * with "Anonymous": the payload still carried the real user id and used it as
 * the key of the answers, so any invited participant could resolve who picked
 * each slot.
 *
 * The API cannot create an anonymous meeting poll (the processor forces
 * anonymous = '0'); such rows only come from a 1.11.x upgrade. The fixture is
 * therefore persisted directly through the entity manager.
 */
class SurveyMeetingAnonymityApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testAnonymousPollDoesNotExposeUserIds(): void
    {
        [$course, $survey, $users, $slots] = $this->createMeetingPoll('1');

        $data = $this->requestPoll($course, $survey, $users[2]);

        $realIds = array_map(static fn (User $user): int => (int) $user->getId(), $users);
        $participantIds = array_column($data['participants'], 'id');

        $this->assertCount(3, $participantIds);
        $this->assertSame([], array_values(array_intersect($participantIds, $realIds)));
        $this->assertSame([], array_values(array_intersect(array_keys($data['matrix']['answers']), $realIds)));
        foreach ($data['participants'] as $participant) {
            $this->assertSame('Anonymous', $participant['name']);
        }

        // The two structures must still match, or the grid cannot be drawn.
        $this->assertSame([], array_values(array_diff(array_keys($data['matrix']['answers']), $participantIds)));

        // The answers themselves survive: one row per respondent, same picks.
        $picks = array_map(
            static fn (array $row): array => array_keys($row),
            array_values($data['matrix']['answers'])
        );
        sort($picks);
        $this->assertSame([[$slots[0]], [$slots[1]]], $picks);
    }

    public function testNonAnonymousPollKeepsUserIds(): void
    {
        [$course, $survey, $users, $slots] = $this->createMeetingPoll('0');

        $data = $this->requestPoll($course, $survey, $users[2]);

        $realIds = array_map(static fn (User $user): int => (int) $user->getId(), $users);
        $participantIds = array_column($data['participants'], 'id');
        sort($participantIds);

        $this->assertSame($realIds, $participantIds);
        $this->assertSame([$slots[0] => 1], $data['matrix']['answers'][$realIds[0]]);
        $this->assertSame([$slots[1] => 1], $data['matrix']['answers'][$realIds[1]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPoll(Course $course, CSurvey $survey, User $user): array
    {
        $response = $this
            ->createClientWithCredentials($this->getUserTokenFromUser($user))
            ->request('GET', '/api/survey/meeting/'.$survey->getIid().'?cid='.$course->getId())
        ;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        return $response->toArray();
    }

    /**
     * Three invited users: the first picks the first slot, the second picks the
     * second slot and the third has not answered.
     *
     * @return array{0: Course, 1: CSurvey, 2: array<int, User>, 3: array<int, int>}
     */
    private function createMeetingPoll(string $anonymous): array
    {
        $em = $this->getEntityManager();
        $suffix = '1' === $anonymous ? 'anon' : 'named';
        $course = $this->createCourse('sma course '.$suffix);

        $survey = (new CSurvey())
            ->setCode('sma_'.$suffix)
            ->setTitle('Meeting poll')
            ->setLang('en_US')
            ->setAvailFrom(new DateTime('-1 day'))
            ->setAvailTill(new DateTime('+1 day'))
            ->setSurveythanks('')
            ->setAnonymous($anonymous)
            ->setVisibleResults(0)
            ->setIsShared('0')
            ->setTemplate('template')
            ->setSurveyType(3)
            ->setSurveyVersion('')
            ->setParent($course)
            ->setCreator($this->getUser('admin'))
            ->addCourseLink($course)
        ;
        $em->persist($survey);

        $slots = [];
        foreach ([1, 2] as $sort) {
            $slot = (new CSurveyQuestion())
                ->setSurvey($survey)
                ->setType('doodle')
                ->setDisplay('horizontal')
                ->setSurveyQuestion('2030-01-0'.$sort.' 09:00:00@@2030-01-0'.$sort.' 10:00:00')
                ->setSurveyQuestionComment('')
                ->setSharedQuestionId(0)
                ->setMaxValue(0)
                ->setSurveyGroupPri(0)
                ->setSurveyGroupSec1(0)
                ->setSurveyGroupSec2(0)
                ->setIsMandatory(false)
                ->setSort($sort)
            ;
            $em->persist($slot);
            $slots[] = $slot;
        }

        $users = [];
        foreach ([0, 1, 2] as $index) {
            $user = $this->createUser('sma_'.$suffix.'_'.$index);
            $users[] = $user;

            $em->persist(
                (new CSurveyInvitation())
                    ->setSurvey($survey)
                    ->setCourse($course)
                    ->setUser($user)
                    ->setInvitationCode('sma-'.$suffix.'-'.$index)
                    ->setAnswered(isset($slots[$index]) ? 1 : 0)
            );

            if (isset($slots[$index])) {
                $em->persist(
                    (new CSurveyAnswer())
                        ->setSurvey($survey)
                        ->setQuestion($slots[$index])
                        ->setUser((string) $user->getId())
                        ->setOptionId('1')
                        ->setValue(1)
                        ->setLpItemId(0)
                        ->setSessionId(null)
                );
            }
        }
        $em->flush();

        return [
            $course,
            $survey,
            $users,
            array_map(static fn (CSurveyQuestion $slot): int => (int) $slot->getIid(), $slots),
        ];
    }
}
