<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\Skill;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SkillFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /*INSERT INTO skill (name, icon, description, short_code, access_url_id, updated_at) VALUES ('Root', '', '', 'root', 1, now());
        INSERT INTO skill_rel_skill VALUES(1, 1, 0, 0, 0);*/

        // @todo check if we still need skill_rel_skill
        $skill = (new Skill())
            ->setName('Root')
            ->setShortCode('root')
            ->setAccessUrlId(1)
        ;
        $manager->persist($skill);

        $manager->flush();
    }
}
