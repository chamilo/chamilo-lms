<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookComment;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\GradebookResultAttempt;
use Chamilo\CoreBundle\Repository\GradeBookCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class GradeBookCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(GradeBookCategoryRepository::class);

        $course = $this->createCourse('new');

        $category = (new GradebookCategory())
            ->setName('cat1')
            ->setCourse($course)
            ->setWeight(100.00)
            ->setVisible(true)
            ->setGenerateCertificates(true)
        ;
        $this->assertHasNoEntityViolations($category);

        $evaluation = (new GradebookEvaluation())
            ->setName('eva')
            ->setCategory($category)
            ->setCourse($course)
            ->setWeight(100.00)
            ->setVisible(1)
            ->setWeight(50.00)
            ->setType('evaluation')
            ->setMax(100.00)
        ;
        $this->assertHasNoEntityViolations($evaluation);

        $link = (new GradebookLink())
            ->setRefId(1)
            ->setCategory($category)
            ->setCourse($course)
            ->setWeight(100.00)
            ->setVisible(1)
            ->setWeight(50.00)
            ->setType(1)
        ;
        $this->assertHasNoEntityViolations($link);

        $category->getLinks()->add($link);
        $category->getEvaluations()->add($evaluation);

        $em->persist($evaluation);
        $em->persist($category);
        $em->flush();

        $this->assertSame(1, $category->getEvaluations()->count());
        $this->assertSame(1, $category->getLinks()->count());
        $this->assertSame(1, $repo->count([]));

        $user = $this->createUser('test');

        $certificate = (new GradebookCertificate())
            ->setUser($user)
            ->setScoreCertificate(100.00)
            ->setCategory($category)
        ;
        $em->persist($certificate);
        $em->flush();

        $comment = (new GradebookComment())
            ->setUser($user)
            ->setGradeBook($category)
            ->setComment('comment')
        ;
        $em->persist($comment);
        $em->flush();

        $result = (new GradebookResult())
            ->setUser($user)
            ->setEvaluation($evaluation)
            ->setScore(100.00)
        ;
        $em->persist($result);
        $em->flush();

        $resultAttempt = (new GradebookResultAttempt())
            ->setResult($result)
            ->setComment('comment')
            ->setScore(100.00)
        ;
        $em->persist($resultAttempt);
        $em->flush();

        $em->remove($category);
        $em->flush();

        $this->assertSame(0, $repo->count([]));
    }
}
