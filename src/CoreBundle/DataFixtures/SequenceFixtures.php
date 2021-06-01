<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SequenceFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //$manager->flush();
    }
}
