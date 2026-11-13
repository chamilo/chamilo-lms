<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelGradebook;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Repository\SkillRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SkillRankingControllerTest extends WebTestCase
{
    use ChamiloTestTrait;

    /**
     * "Currently learning" sums the skills linked to the gradebooks of each course the user follows. The legacy
     * ranking counted each course once; a learner subscribed to a course both directly and through a session
     * must not see the skills of that course twice.
     */
    public function testCourseFollowedDirectlyAndThroughASessionCountsOnce(): void
    {
        $client = static::createClient();
        $em = $this->getEntityManager();
        $skillRepo = self::getContainer()->get(SkillRepository::class);
        $suffix = uniqid();

        $course = $this->createCourse('skill_ranking_course_'.$suffix);
        $category = (new GradebookCategory())
            ->setTitle('skill_ranking_gradebook')
            ->setCourse($course)
            ->setVisible(true)
            ->setWeight(100)
        ;
        $em->persist($category);

        $skills = [];
        foreach (['a', 'b'] as $code) {
            $skill = (new Skill())
                ->setTitle('skill_ranking_'.$code.'_'.$suffix)
                ->setShortCode($code)
                ->setStatus(Skill::STATUS_ENABLED)
                ->setAccessUrlId($this->getAccessUrl()->getId())
            ;
            $skillRepo->update($skill);
            $em->persist(
                (new SkillRelGradebook())
                    ->setSkill($skill)
                    ->setType('type')
                    ->setGradeBookCategory($category)
            );
            $skills[] = $skill;
        }

        $learner = $this->createUser('skill_ranking_learner_'.$suffix);
        $learner->setFirstname('SkillRanking'.$suffix);
        $course->addUserAsStudent($learner);

        $session = $this->createSession('skill_ranking_session_'.$suffix);
        $session->addCourse($course);
        $session->addUserInCourse(Session::STUDENT, $learner, $course);

        // An acquired skill puts the learner in the ranking.
        $em->persist(
            (new SkillRelUser())
                ->setSkill($skills[0])
                ->setCourse($course)
                ->setUser($learner)
                ->setArgumentation('argumentation')
                ->setArgumentationAuthorId(1)
        );
        $em->flush();

        $client->loginUser($this->getAdmin());
        $client->request('GET', '/skill/ranking-data');
        $this->assertResponseIsSuccessful();

        $rows = array_values(array_filter(
            json_decode((string) $client->getResponse()->getContent(), true),
            static fn (array $row): bool => 'SkillRanking'.$suffix === $row['firstname'],
        ));

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]['skillsAcquired']);
        $this->assertSame(2, $rows[0]['currentlyLearning'], 'the 2 skills of the course, not 4');
    }
}
