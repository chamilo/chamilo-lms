<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\CourseType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $list = [
            'All tools',
            'Entry exam',
        ];

        foreach ($list as $name) {
            $courseType = (new CourseType())
                ->setName($name)
            ;
            $manager->persist($courseType);
        }
        $manager->flush();
    }
}
