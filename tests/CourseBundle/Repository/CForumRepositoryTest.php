<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CForumRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getManager();
        $repo = self::getContainer()->get(CForumRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $item = (new CForum())
            ->setForumTitle('forum')
            ->setParent($course)
            ->setCreator($teacher)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('forum', (string) $item);
        $this->assertSame(1, $repo->count([]));
    }
}
