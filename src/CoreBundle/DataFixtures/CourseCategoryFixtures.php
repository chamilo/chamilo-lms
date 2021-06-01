<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\CourseCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseCategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $list = [
            [
                'name' => 'Language skills',
                'code' => 'LANG',
            ],
            [
                'name' => 'PC Skills',
                'code' => 'PC',
            ],
            [
                'name' => 'Projects',
                'code' => 'PROJ',
            ],
        ];

        $url = $this->getReference(AccessUrlAdminFixtures::ACCESS_URL_REFERENCE);

        foreach ($list as $key => $data) {
            $courseCategory = (new CourseCategory())
                ->setName($data['name'])
                ->setCode($data['code'])
                ->setTreePos($key + 1)
                ->addUrl($url)
            ;
            $manager->persist($courseCategory);
        }

        $manager->flush();
    }
}
