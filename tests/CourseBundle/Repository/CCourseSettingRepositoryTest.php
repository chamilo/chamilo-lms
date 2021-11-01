<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Repository\CCourseSettingRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CCourseSettingRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CCourseSettingRepository::class);
        $count = $repo->count([]);

        $course = $this->createCourse('new');
        $courseId = $course->getId();

        $item = (new CCourseSetting())
            ->setTitle('test')
            ->setVariable('test')
            ->setCategory('cat')
            ->setSubkey('subkey')
            ->setType('type')
            ->setComment('comment')
            ->setSubkeytext('subkey text')
            ->setCId($courseId)
        ;
        $this->assertHasNoEntityViolations($item);
        $em->persist($item);
        $em->flush();

        $this->assertSame('test', $item->getVariable());
        $this->assertSame('subkey', $item->getSubkey());
        $this->assertSame('subkey text', $item->getSubkeytext());
        $this->assertSame('type', $item->getType());
        $this->assertSame('comment', $item->getComment());
        $this->assertSame($courseId, $item->getCId());
        $this->assertSame('test', $item->getTitle());
        $this->assertSame('cat', $item->getCategory());
        $this->assertSame($count + 1, $repo->count([]));
    }
}
