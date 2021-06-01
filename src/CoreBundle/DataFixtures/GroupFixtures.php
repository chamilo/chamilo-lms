<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\Group;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GroupFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $groups = [
            [
                'code' => 'ADMIN',
                'title' => 'Administrators',
                'roles' => ['ROLE_ADMIN'],
            ],
            [
                'code' => 'STUDENT',
                'title' => 'Students',
                'roles' => ['ROLE_STUDENT'],
            ],
            [
                'code' => 'TEACHER',
                'title' => 'Teachers',
                'roles' => ['ROLE_TEACHER'],
            ],
            [
                'code' => 'RRHH',
                'title' => 'Human resources manager',
                'roles' => ['ROLE_RRHH'],
            ],
            [
                'code' => 'SESSION_MANAGER',
                'title' => 'Session',
                'roles' => ['ROLE_SESSION_MANAGER'],
            ],
            [
                'code' => 'QUESTION_MANAGER',
                'title' => 'Question manager',
                'roles' => ['ROLE_QUESTION_MANAGER'],
            ],
            [
                'code' => 'STUDENT_BOSS',
                'title' => 'Student boss',
                'roles' => ['ROLE_STUDENT_BOSS'],
            ],
            [
                'code' => 'INVITEE',
                'title' => 'Invitee',
                'roles' => ['ROLE_INVITEE'],
            ],
        ];
        $repo = $manager->getRepository(Group::class);
        foreach ($groups as $groupData) {
            $criteria = [
                'code' => $groupData['code'],
            ];
            $groupExists = $repo->findOneBy($criteria);
            if (!$groupExists) {
                $group = new Group($groupData['title']);
                $group
                    ->setCode($groupData['code'])
                ;

                foreach ($groupData['roles'] as $role) {
                    $group->addRole($role);
                }
                $manager->persist($group);
            }
        }
        $manager->flush();
    }
}
