<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\ResourceFormat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ResourceFormatFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $list = [
            [
                'name' => 'html',
            ],
            [
                'name' => 'txt',
            ],
        ];

        foreach ($list as $key => $data) {
            $resourceFormat = (new ResourceFormat())
                ->setName($data['name'])
            ;
            $manager->persist($resourceFormat);
        }

        $manager->flush();
    }
}
