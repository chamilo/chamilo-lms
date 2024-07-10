<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\Permission;
use Chamilo\CoreBundle\Entity\PermissionRelRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class PermissionFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['permissions'];
    }

    public function load(ObjectManager $manager): void
    {
        $permissions = self::getPermissions();
        $roles = self::getRoles();
        $permissionsMapping = self::getPermissionsMapping();

        foreach ($permissions as $permData) {
            $existingPermission = $manager->getRepository(Permission::class)->findOneBy(['slug' => $permData['slug']]);
            if ($existingPermission) {
                continue;
            }

            $permission = new Permission();
            $permission->setTitle($permData['title']);
            $permission->setSlug($permData['slug']);
            $permission->setDescription($permData['description']);
            $manager->persist($permission);

            $manager->flush();

            foreach ($roles as $roleName => $roleCode) {
                if (in_array($roleCode, $permissionsMapping[$permData['slug']])) {
                    $permRelRole = new PermissionRelRole();
                    $permRelRole->setPermission($permission);
                    $permRelRole->setRoleCode($roleName);
                    $permRelRole->setChangeable(true);
                    $permRelRole->setUpdatedAt(new \DateTime());
                    $manager->persist($permRelRole);
                }
            }
        }

        $manager->flush();
    }

    public static function getPermissions(): array
    {
        return [
            ['title' => 'View Analytics', 'slug' => 'analytics:view', 'description' => 'View analytics data'],
            ['title' => 'View Assigned Analytics', 'slug' => 'analytics:viewassigned', 'description' => 'View results of users assigned to me'],
            ['title' => 'View All Analytics', 'slug' => 'analytics:viewall', 'description' => 'View results of all users'],
            ['title' => 'Create Assignment', 'slug' => 'assignment:create', 'description' => 'Create assignments'],
            ['title' => 'Delete Assignment', 'slug' => 'assignment:delete', 'description' => 'Delete assignments'],
            ['title' => 'Edit Assignment', 'slug' => 'assignment:edit', 'description' => 'Edit assignments'],
            ['title' => 'Grade Assignment', 'slug' => 'assignment:grade', 'description' => 'Grade assignments'],
            ['title' => 'Submit Assignment', 'slug' => 'assignment:submit', 'description' => 'Submit assignments'],
            ['title' => 'View Assignment', 'slug' => 'assignment:view', 'description' => 'View assignments'],
            ['title' => 'Backup', 'slug' => 'backup:backup', 'description' => 'Backup'],
            ['title' => 'Copy Backup', 'slug' => 'backup:copy', 'description' => 'Copy backup'],
            ['title' => 'Restore Backup', 'slug' => 'backup:restore', 'description' => 'Restore backup'],
            ['title' => 'Configure Badge Criteria', 'slug' => 'badge:configurecriteria', 'description' => 'Configure badge criteria'],
            ['title' => 'Create Badge', 'slug' => 'badge:create', 'description' => 'Create badges'],
            ['title' => 'Edit Badge', 'slug' => 'badge:edit', 'description' => 'Edit badges'],
            ['title' => 'Delete Badge', 'slug' => 'badge:delete', 'description' => 'Delete badges'],
            ['title' => 'View Badge', 'slug' => 'badge:view', 'description' => 'View badges'],
            ['title' => 'Create Calendar Event', 'slug' => 'calendar:create', 'description' => 'Create calendar events'],
            ['title' => 'Edit Calendar Event', 'slug' => 'calendar:edit', 'description' => 'Edit calendar events'],
            ['title' => 'Delete Calendar Event', 'slug' => 'calendar:delete', 'description' => 'Delete calendar events'],
            ['title' => 'View Courses Catalogue', 'slug' => 'catalogue:view', 'description' => 'View courses catalogue'],
            ['title' => 'Create Certificate Template', 'slug' => 'certificate:create', 'description' => 'Create certificate templates'],
            ['title' => 'Delete Certificate Template', 'slug' => 'certificate:delete', 'description' => 'Delete certificate templates'],
            ['title' => 'Edit Certificate Template', 'slug' => 'certificate:edit', 'description' => 'Edit certificate templates'],
            ['title' => 'Generate Certificate', 'slug' => 'certificate:generate', 'description' => 'Generate certificates'],
            ['title' => 'Generate All Certificates', 'slug' => 'certificate:generateall', 'description' => 'Generate all certificates in a gradebook'],
            ['title' => 'View All Certificates', 'slug' => 'certificate:viewall', 'description' => 'View all instances of one certificate issued to all users'],
            ['title' => 'Assign Course to Class', 'slug' => 'class:assigncourse', 'description' => 'Assign a course to a class'],
            ['title' => 'Assign Session to Class', 'slug' => 'class:assignsession', 'description' => 'Assign a session to a class'],
            ['title' => 'Assign User to Class', 'slug' => 'class:assignuser', 'description' => 'Assign a user to a class'],
            ['title' => 'Create Class', 'slug' => 'class:create', 'description' => 'Manage global classes of users'],
            ['title' => 'Delete Class', 'slug' => 'class:delete', 'description' => 'Delete classes'],
            ['title' => 'Edit Class', 'slug' => 'class:edit', 'description' => 'Edit classes'],
            ['title' => 'View Class', 'slug' => 'class:view', 'description' => 'View classes'],
            ['title' => 'Create CMS Page', 'slug' => 'cms:create', 'description' => 'Create CMS pages'],
            ['title' => 'Delete CMS Page', 'slug' => 'cms:delete', 'description' => 'Delete CMS pages'],
            ['title' => 'Edit CMS Page', 'slug' => 'cms:edit', 'description' => 'Edit CMS pages'],
            ['title' => 'Create Course Space', 'slug' => 'course:create', 'description' => 'Create course spaces'],
            ['title' => 'Delete Course Space', 'slug' => 'course:delete', 'description' => 'Delete course spaces'],
            ['title' => 'Download Course Content', 'slug' => 'course:downloadcoursecontent', 'description' => 'Download all course content'],
            ['title' => 'Edit Own Course Properties', 'slug' => 'course:edit', 'description' => 'Edit own course\'s properties'],
            ['title' => 'Edit All Course Properties', 'slug' => 'course:editall', 'description' => 'Edit all course\'s properties'],
            ['title' => 'Manage Plugins', 'slug' => 'plugin:manage', 'description' => 'Enable/disable/configure plugins'],
            ['title' => 'Create Quiz', 'slug' => 'quiz:create', 'description' => 'Create quizzes'],
            ['title' => 'Delete Quiz', 'slug' => 'quiz:delete', 'description' => 'Delete quizzes'],
            ['title' => 'Edit Quiz', 'slug' => 'quiz:edit', 'description' => 'Edit quizzes'],
            ['title' => 'Grade Quiz', 'slug' => 'quiz:grade', 'description' => 'Grade quizzes'],
            ['title' => 'View Live Quiz Results', 'slug' => 'quiz:viewliveresults', 'description' => 'View live quiz results'],
            ['title' => 'Manage Question Bank', 'slug' => 'quiz:managequestionbank', 'description' => 'Manage question bank'],
            ['title' => 'Create Role', 'slug' => 'role:create', 'description' => 'Create roles'],
            ['title' => 'Manage Role Permissions', 'slug' => 'role:managepermissions', 'description' => 'Assign or remove permissions from roles'],
            ['title' => 'Create Session', 'slug' => 'session:create', 'description' => 'Create sessions'],
            ['title' => 'Delete Session', 'slug' => 'session:delete', 'description' => 'Delete sessions'],
            ['title' => 'Edit Own Session Properties', 'slug' => 'session:edit', 'description' => 'Edit own session\'s properties'],
            ['title' => 'Edit All Session Properties', 'slug' => 'session:editall', 'description' => 'Edit all session\'s properties'],
            ['title' => 'Assign Course to Session', 'slug' => 'session:assigncourse', 'description' => 'Assign a course to a session'],
            ['title' => 'Edit Site Settings', 'slug' => 'site:editsettings', 'description' => 'Manage settings of the platform'],
            ['title' => 'Access Site Maintenance', 'slug' => 'site:maintenanceaccess', 'description' => 'Access site maintenance'],
            ['title' => 'Manage Course Competency', 'slug' => 'skill:coursecompetencymanage', 'description' => 'Assign skills through course gradebooks'],
            ['title' => 'Review User Competency', 'slug' => 'skill:usercompetencyreview', 'description' => 'Add comments on other user\'s acquired skills'],
            ['title' => 'Assign Skill', 'slug' => 'skill:assign', 'description' => 'Assign a skill to a user'],
            ['title' => 'Create Skill', 'slug' => 'skill:create', 'description' => 'Create skills'],
            ['title' => 'Delete Skill', 'slug' => 'skill:delete', 'description' => 'Delete skills'],
            ['title' => 'Edit Skill', 'slug' => 'skill:edit', 'description' => 'Edit skills'],
            ['title' => 'View Skill', 'slug' => 'skill:view', 'description' => 'View all skills acquired by users in my context'],
            ['title' => 'View All Skills', 'slug' => 'skill:viewall', 'description' => 'View all skills acquired by users of the platform'],
            ['title' => 'Create Survey', 'slug' => 'survey:create', 'description' => 'Add a survey (global or inside own course)'],
            ['title' => 'Delete Survey', 'slug' => 'survey:delete', 'description' => 'Delete surveys'],
            ['title' => 'Edit Survey', 'slug' => 'survey:edit', 'description' => 'Edit surveys'],
            ['title' => 'Submit Survey', 'slug' => 'survey:submit', 'description' => 'Submit surveys'],
            ['title' => 'View Survey Results', 'slug' => 'survey:viewresults', 'description' => 'View survey results'],
            ['title' => 'Comment on Ticket', 'slug' => 'ticket:comment', 'description' => 'Comment on tickets'],
            ['title' => 'Manage Tickets', 'slug' => 'ticket:manage', 'description' => 'Manage the tickets system'],
            ['title' => 'Report Ticket', 'slug' => 'ticket:report', 'description' => 'Report tickets'],
            ['title' => 'See Ticket Issues', 'slug' => 'ticket:seeissues', 'description' => 'See issue details for issues where they are involved'],
            ['title' => 'View All Ticket Issues', 'slug' => 'ticket:viewallissues', 'description' => 'View all issues'],
            ['title' => 'Edit Tool Visibility', 'slug' => 'tool:editvisibility', 'description' => 'Allow setting the visibility of a tool in a course'],
            ['title' => 'Manage URL', 'slug' => 'url:manage', 'description' => 'Manage Multi-URL configuration'],
            ['title' => 'Assign Users to URL', 'slug' => 'url:assignusers', 'description' => 'Assign users to URL'],
            ['title' => 'Assign Courses to URL', 'slug' => 'url:assigncourses', 'description' => 'Assign courses to URL'],
            ['title' => 'Assign Classes to URL', 'slug' => 'url:assignclasses', 'description' => 'Assign classes to URL'],
            ['title' => 'Assign User to Class', 'slug' => 'user:assignclass', 'description' => 'Assign a user to a class'],
            ['title' => 'Assign User to Course', 'slug' => 'user:assigncourse', 'description' => 'Assign a user to a course'],
            ['title' => 'Assign User to Session', 'slug' => 'user:assignsession', 'description' => 'Assign a user to a session'],
            ['title' => 'Create User', 'slug' => 'user:create', 'description' => 'Create users'],
            ['title' => 'Delete User', 'slug' => 'user:delete', 'description' => 'Delete users'],
            ['title' => 'Edit User', 'slug' => 'user:edit', 'description' => 'Edit users'],
            ['title' => 'Edit User Role', 'slug' => 'user:editrole', 'description' => 'Edit user roles'],
            ['title' => 'Login As User', 'slug' => 'user:loginas', 'description' => 'Login as another user'],
        ];
    }

    public static function getRoles(): array
    {
        return [
            'ROLE_INVITEE' => 'INV',
            'ROLE_STUDENT' => 'STU',
            'ROLE_TEACHER' => 'TEA',
            'ROLE_ADMIN' => 'ADM',
            'ROLE_SUPER_ADMIN' => 'SUA',
            'ROLE_GLOBAL_ADMIN' => 'GLO',
            'ROLE_RRHH' => 'HRM',
            'ROLE_QUESTION_MANAGER' => 'QBM',
            'ROLE_SESSION_MANAGER' => 'SSM',
            'ROLE_STUDENT_BOSS' => 'STB',
        ];
    }

    public static function getPermissionsMapping(): array
    {
        return [
            'analytics:view' => ['INV', 'STU', 'TEA', 'ADM', 'SUA', 'GLO', 'HRM', 'QBM', 'SSM', 'STB'],
            'analytics:viewassigned' => ['TEA', 'ADM', 'SUA', 'GLO', 'HRM', 'SSM', 'STB'],
            'analytics:viewall' => ['ADM', 'SUA', 'GLO', 'SSM', 'STB'],
            'assignment:create' => ['TEA'],
            'assignment:delete' => ['TEA', 'ADM', 'SUA', 'GLO'],
            'assignment:edit' => ['TEA', 'ADM', 'SUA', 'GLO'],
            'assignment:grade' => ['TEA'],
            'assignment:submit' => ['STU'],
            'assignment:view' => ['INV', 'STU', 'TEA', 'ADM', 'SUA', 'GLO', 'HRM', 'SSM', 'STB'],
            'backup:backup' => ['TEA', 'ADM', 'SUA', 'GLO', 'SSM'],
            'backup:copy' => ['TEA', 'ADM', 'SUA', 'GLO', 'SSM'],
            'backup:restore' => ['TEA', 'ADM', 'SUA', 'GLO', 'SSM'],
            'badge:configurecriteria' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'badge:create' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'badge:edit' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'badge:delete' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'badge:view' => ['INV', 'STU', 'TEA', 'ADM', 'SUA', 'GLO', 'SSM', 'STB'],
            'calendar:create' => ['ADM', 'SUA', 'GLO'],
            'calendar:edit' => ['ADM', 'SUA', 'GLO'],
            'calendar:delete' => ['ADM', 'SUA', 'GLO'],
            'catalogue:view' => ['INV', 'STU', 'TEA', 'ADM', 'SUA', 'GLO', 'HRM', 'QBM', 'SSM', 'STB'],
            'certificate:create' => ['TEA', 'SSM'],
            'certificate:delete' => ['TEA', 'SSM'],
            'certificate:edit' => ['TEA', 'SSM'],
            'certificate:generate' => ['STU', 'TEA', 'SSM'],
            'certificate:generateall' => ['TEA', 'HRM', 'SSM'],
            'certificate:viewall' => ['TEA', 'HRM', 'SSM', 'STB'],
            'class:assigncourse' => ['TEA', 'ADM', 'SUA', 'GLO'],
            'class:assignsession' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'class:assignuser' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'class:create' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'class:delete' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'class:edit' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'class:view' => ['STU', 'TEA', 'ADM', 'SUA', 'GLO', 'SSM'],
            'cms:create' => ['ADM', 'SUA', 'GLO'],
            'cms:delete' => ['ADM', 'SUA', 'GLO'],
            'cms:edit' => ['ADM', 'SUA', 'GLO'],
            'course:create' => ['TEA', 'ADM', 'SUA', 'GLO', 'SSM'],
            'course:delete' => ['TEA', 'ADM', 'SUA', 'GLO'],
            'course:downloadcoursecontent' => ['TEA', 'ADM', 'SUA', 'GLO', 'SSM'],
            'course:edit' => ['TEA', 'SSM'],
            'course:editall' => ['ADM', 'SUA', 'GLO'],
            'plugin:manage' => ['ADM', 'SUA', 'GLO'],
            'quiz:create' => ['TEA', 'QBM'],
            'quiz:delete' => ['TEA', 'QBM'],
            'quiz:edit' => ['TEA', 'QBM'],
            'quiz:grade' => ['TEA'],
            'quiz:viewliveresults' => ['TEA', 'SSM'],
            'quiz:managequestionbank' => ['ADM', 'SUA', 'GLO', 'QBM'],
            'role:create' => ['ADM', 'SUA', 'GLO'],
            'role:managepermissions' => ['ADM', 'SUA', 'GLO'],
            'session:create' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'session:delete' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'session:edit' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'session:editall' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'session:assigncourse' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'site:editsettings' => ['ADM', 'SUA', 'GLO'],
            'site:maintenanceaccess' => ['ADM', 'SUA', 'GLO'],
            'skill:coursecompetencymanage' => ['TEA', 'ADM', 'SUA', 'GLO', 'HRM'],
            'skill:usercompetencyreview' => ['STU', 'TEA', 'ADM', 'SUA', 'GLO'],
            'skill:assign' => ['ADM', 'SUA', 'GLO'],
            'skill:create' => ['GLO'],
            'skill:delete' => ['GLO'],
            'skill:edit' => ['GLO'],
            'skill:view' => ['ADM', 'SUA', 'GLO', 'SSM', 'STB'],
            'skill:viewall' => ['ADM', 'SUA', 'GLO', 'SSM', 'STB'],
            'survey:create' => ['TEA'],
            'survey:delete' => ['TEA'],
            'survey:edit' => ['TEA'],
            'survey:submit' => ['INV', 'STU', 'TEA', 'ADM', 'SUA', 'GLO', 'SSM', 'STB'],
            'survey:viewresults' => ['TEA', 'HRM', 'SSM', 'STB'],
            'ticket:comment' => ['STU', 'TEA', 'ADM', 'SUA', 'GLO', 'HRM', 'QBM', 'SSM', 'STB'],
            'ticket:manage' => ['ADM', 'SUA', 'GLO'],
            'ticket:report' => ['STU', 'TEA', 'ADM', 'SUA', 'GLO', 'HRM', 'QBM', 'SSM', 'STB'],
            'ticket:seeissues' => ['STU', 'TEA', 'ADM', 'SUA', 'GLO', 'SSM', 'STB'],
            'ticket:viewallissues' => ['ADM', 'SUA', 'GLO', 'SSM', 'STB'],
            'tool:editvisibility' => ['TEA', 'ADM', 'SUA', 'GLO', 'SSM'],
            'url:manage' => ['GLO'],
            'url:assignusers' => ['GLO'],
            'url:assigncourses' => ['GLO'],
            'url:assignclasses' => ['GLO'],
            'user:assignclass' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'user:assigncourse' => ['TEA', 'ADM', 'SUA', 'GLO'],
            'user:assignsession' => ['ADM', 'SUA', 'GLO', 'SSM'],
            'user:create' => ['ADM', 'SUA', 'GLO'],
            'user:delete' => ['ADM', 'SUA', 'GLO'],
            'user:edit' => ['ADM', 'SUA', 'GLO'],
            'user:editrole' => ['ADM', 'SUA', 'GLO'],
            'user:loginas' => ['SUA', 'GLO'],
        ];
    }
}
