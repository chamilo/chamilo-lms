<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $roles = [
            [
                'code' => 'INVITEE',
                'constantValue' => 1,
                'title' => 'Invitee',
                'description' => 'Invited users',
                'systemRole' => true,
            ],
            [
                'code' => 'STUDENT',
                'constantValue' => 2,
                'title' => 'Student',
                'description' => 'Students of courses or sessions',
                'systemRole' => true,
            ],
            [
                'code' => 'TEACHER',
                'constantValue' => 3,
                'title' => 'Teacher',
                'description' => 'Teachers of courses or sessions',
                'systemRole' => true,
            ],
            [
                'code' => 'ADMIN',
                'constantValue' => 4,
                'title' => 'Administrator',
                'description' => 'Platform administrators',
                'systemRole' => true,
            ],
            [
                'code' => 'SUPER_ADMIN',
                'constantValue' => 5,
                'title' => 'Super Administrator',
                'description' => 'Super admin users',
                'systemRole' => true,
            ],
            [
                'code' => 'GLOBAL_ADMIN',
                'constantValue' => 6,
                'title' => 'Global Administrator',
                'description' => 'Global admin users',
                'systemRole' => true,
            ],
            [
                'code' => 'HR',
                'constantValue' => 7,
                'title' => 'HR Manager',
                'description' => 'Human resources managers',
                'systemRole' => false,
            ],
            [
                'code' => 'QUESTION_MANAGER',
                'constantValue' => 8,
                'title' => 'Question Bank Manager',
                'description' => 'Manages the question bank across courses',
                'systemRole' => false,
            ],
            [
                'code' => 'SESSION_MANAGER',
                'constantValue' => 9,
                'title' => 'Session Manager',
                'description' => 'Manages sessions and session content',
                'systemRole' => false,
            ],
            [
                'code' => 'STUDENT_BOSS',
                'constantValue' => 10,
                'title' => 'Student Boss',
                'description' => 'Manages groups of students',
                'systemRole' => false,
            ],
        ];

        foreach ($roles as $roleData) {
            $existing = $manager->getRepository(Role::class)
                ->findOneBy(['code' => $roleData['code']]);

            if ($existing) {
                continue;
            }

            $role = new Role();
            $role->setCode($roleData['code']);
            $role->setConstantValue($roleData['constantValue']);
            $role->setTitle($roleData['title']);
            $role->setDescription($roleData['description']);
            $role->setSystemRole($roleData['systemRole']);
            $manager->persist($role);
        }

        $manager->flush();
    }
}
