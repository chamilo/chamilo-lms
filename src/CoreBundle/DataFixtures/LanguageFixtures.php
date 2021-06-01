<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\Language;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LanguageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /*$list = [
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

        foreach ($list as $data) {
            $lang = (new Language())
                ->set($data['name'])
                ->setCode($data['code'])
                ->setTreePos($key + 1)
            ;
            $manager->persist($lang);
        }*/
        // $product = new Product();
        // $manager->persist($product);

        //$manager->flush();
    }
}
