<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Repository\GradeBookCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class GradeBookCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getManager();
        $repo = self::getContainer()->get(GradeBookCategoryRepository::class);

        $course = $this->createCourse('new');

        $item = (new GradebookCategory())
            ->setName('cat1')
            ->setCourse($course)
            ->setWeight(100.00)
            ->setVisible(true)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame(1, $repo->count([]));
    }
}
