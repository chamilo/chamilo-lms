<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Repository\CourseCategoryRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CourseCategoryRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get('doctrine')->getManager();
        $repo = self::getContainer()->get(CourseCategoryRepository::class);

        $item = (new CourseCategory())
            ->setCode('Course cat')
            ->setName('Course cat')
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        // On a fresh installation there are already 3 categories.
        // See the src/CoreBundle/DataFixtures/CourseCategoryFixtures.php
        $this->assertSame(4, $repo->count([]));
    }
}
