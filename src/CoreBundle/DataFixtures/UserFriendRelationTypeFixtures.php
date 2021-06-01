<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\UserFriendRelationType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFriendRelationTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // @todo add translations
        $list = [
            'Unknown',
            'My parents',
            'My friends',
            'My real friends',
            'My enemies',
            'Contact deleted',
        ];

        foreach ($list as $title) {
            $userFriend = (new UserFriendRelationType())
                ->setTitle($title)
            ;
            $manager->persist($userFriend);
        }

        $manager->flush();
    }
}
