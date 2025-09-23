<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\Permission;
use Chamilo\CoreBundle\Entity\PermissionRelRole;
use Chamilo\CoreBundle\Entity\Role;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;

class PermissionFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            RoleFixtures::class,
        ];
    }

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
                if (\in_array($roleCode, $permissionsMapping[$permData['slug']])) {
                    $roleEntity = $manager->getRepository(Role::class)->findOneBy([
                        'code' => substr($roleName, 5),
                    ]);

                    if (!$roleEntity) {
                        throw new RuntimeException('Role entity not found for code: '.$roleName);
                    }

                    $permRelRole = new PermissionRelRole();
                    $permRelRole->setPermission($permission);
                    $permRelRole->setRole($roleEntity);
                    $permRelRole->setChangeable(true);
                    $permRelRole->setUpdatedAt(new DateTime());
                    $manager->persist($permRelRole);
                }
            }
        }

        $manager->flush();
    }

    public static function getPermissions(): array
    {
        return [
            ['title' => 'View analytics', 'slug' => 'analytics:view', 'description' => 'View analytics data'],
            ['title' => 'View assigned analytics', 'slug' => 'analytics:viewassigned', 'description' => 'View results of users assigned to me'],
            ['title' => 'View all analytics', 'slug' => 'analytics:viewall', 'description' => 'View results of all users'],
            ['title' => 'Create assignment', 'slug' => 'assignment:create', 'description' => 'Create assignments'],
            ['title' => 'Delete assignment', 'slug' => 'assignment:delete', 'description' => 'Delete assignments'],
            ['title' => 'Edit assignment', 'slug' => 'assignment:edit', 'description' => 'Edit assignments'],
            ['title' => 'Grade assignment', 'slug' => 'assignment:grade', 'description' => 'Grade assignments'],
            ['title' => 'Submit assignment', 'slug' => 'assignment:submit', 'description' => 'Submit assignments'],
            ['title' => 'View assignment', 'slug' => 'assignment:view', 'description' => 'View assignments'],
            ['title' => 'Backup', 'slug' => 'backup:backup', 'description' => 'Backup'],
            ['title' => 'Copy backup', 'slug' => 'backup:copy', 'description' => 'Copy course content to another course'],
            ['title' => 'Restore backup', 'slug' => 'backup:restore', 'description' => 'Restore backup'],
            ['title' => 'Configure badge criteria', 'slug' => 'badge:configurecriteria', 'description' => 'Configure badge criteria'],
            ['title' => 'Create badge', 'slug' => 'badge:create', 'description' => 'Create badges'],
            ['title' => 'Delete badge', 'slug' => 'badge:delete', 'description' => 'Delete badges'],
            ['title' => 'Edit badge', 'slug' => 'badge:edit', 'description' => 'Edit badges'],
            ['title' => 'View badge', 'slug' => 'badge:view', 'description' => 'View badges'],
            ['title' => 'Create calendar event', 'slug' => 'calendar:create', 'description' => 'Create calendar events'],
            ['title' => 'Delete calendar event', 'slug' => 'calendar:delete', 'description' => 'Delete calendar events'],
            ['title' => 'Edit calendar event', 'slug' => 'calendar:edit', 'description' => 'Edit calendar events'],
            ['title' => 'View courses catalogue', 'slug' => 'catalogue:view', 'description' => 'View courses catalogue'],
            ['title' => 'Create certificate template', 'slug' => 'certificate:create', 'description' => 'Create certificate templates'],
            ['title' => 'Delete certificate template', 'slug' => 'certificate:delete', 'description' => 'Delete certificate templates'],
            ['title' => 'Edit certificate template', 'slug' => 'certificate:edit', 'description' => 'Edit certificate templates'],
            ['title' => 'Generate certificate', 'slug' => 'certificate:generate', 'description' => 'Generate certificates'],
            ['title' => 'Generate all certificates', 'slug' => 'certificate:generateall', 'description' => 'Generate all certificates in a gradebook'],
            ['title' => 'View all certificates', 'slug' => 'certificate:viewall', 'description' => 'View all instances of one certificate issued to all users'],
            ['title' => 'Assign course to class', 'slug' => 'class:assigncourse', 'description' => 'Assign a class to a course'],
            ['title' => 'Assign cession to class', 'slug' => 'class:assignsession', 'description' => 'Assign a class to a session'],
            ['title' => 'Assign user to class', 'slug' => 'class:assignuser', 'description' => 'Assign a user to a class'],
            ['title' => 'Create class', 'slug' => 'class:create', 'description' => 'Create global classes of users'],
            ['title' => 'Delete class', 'slug' => 'class:delete', 'description' => 'Delete global classes'],
            ['title' => 'Edit class', 'slug' => 'class:edit', 'description' => 'Edit global classes'],
            ['title' => 'View class', 'slug' => 'class:view', 'description' => 'View global classes details'],
            ['title' => 'Create CMS page', 'slug' => 'cms:create', 'description' => 'Create CMS pages'],
            ['title' => 'Delete CMS page', 'slug' => 'cms:delete', 'description' => 'Delete CMS pages'],
            ['title' => 'Edit CMS page', 'slug' => 'cms:edit', 'description' => 'Edit CMS pages'],
            ['title' => 'Create course space', 'slug' => 'course:create', 'description' => 'Create courses'],
            ['title' => 'Delete course space', 'slug' => 'course:delete', 'description' => 'Delete courses'],
            ['title' => 'Download course content', 'slug' => 'course:downloadcoursecontent', 'description' => 'Download all course content'],
            ['title' => 'Edit own course properties', 'slug' => 'course:edit', 'description' => 'Edit own course\'s properties'],
            ['title' => 'Edit all course properties', 'slug' => 'course:editall', 'description' => 'Edit all course\'s properties'],
            ['title' => 'Manage plugins', 'slug' => 'plugin:manage', 'description' => 'Enable/disable/configure plugins'],
            ['title' => 'Create quiz', 'slug' => 'quiz:create', 'description' => 'Create quizzes'],
            ['title' => 'Delete quiz', 'slug' => 'quiz:delete', 'description' => 'Delete quizzes'],
            ['title' => 'Edit quiz', 'slug' => 'quiz:edit', 'description' => 'Edit quizzes'],
            ['title' => 'Grade quiz', 'slug' => 'quiz:grade', 'description' => 'Grade quizzes'],
            ['title' => 'View live quiz results', 'slug' => 'quiz:viewliveresults', 'description' => 'View live quiz results'],
            ['title' => 'Manage question bank', 'slug' => 'quiz:managequestionbank', 'description' => 'Manage question bank'],
            ['title' => 'Create role', 'slug' => 'role:create', 'description' => 'Create roles'],
            ['title' => 'Manage role permissions', 'slug' => 'role:managepermissions', 'description' => 'Assign or remove permissions from roles'],
            ['title' => 'Create session', 'slug' => 'session:create', 'description' => 'Create sessions'],
            ['title' => 'Delete session', 'slug' => 'session:delete', 'description' => 'Delete sessions'],
            ['title' => 'Edit own session properties', 'slug' => 'session:edit', 'description' => 'Edit properties of user\'s own sessions'],
            ['title' => 'Edit all session properties', 'slug' => 'session:editall', 'description' => 'Edit properties of all sessions'],
            ['title' => 'Assign course to session', 'slug' => 'session:assigncourse', 'description' => 'Assign a course to a session'],
            ['title' => 'Edit site settings', 'slug' => 'site:editsettings', 'description' => 'Manage settings of the platform'],
            ['title' => 'Access site maintenance', 'slug' => 'site:maintenanceaccess', 'description' => 'Access site maintenance'],
            ['title' => 'Manage course competency', 'slug' => 'skill:coursecompetencymanage', 'description' => 'Assign skills through course grade books'],
            ['title' => 'Review user competency', 'slug' => 'skill:usercompetencyreview', 'description' => 'Add comments on other user\'s acquired skills'],
            ['title' => 'Assign skill', 'slug' => 'skill:assign', 'description' => 'Assign a skill to a user'],
            ['title' => 'Create skill', 'slug' => 'skill:create', 'description' => 'Create skills'],
            ['title' => 'Delete skill', 'slug' => 'skill:delete', 'description' => 'Delete skills'],
            ['title' => 'Edit skill', 'slug' => 'skill:edit', 'description' => 'Edit skills'],
            ['title' => 'View skill', 'slug' => 'skill:view', 'description' => 'View all skills acquired by users in manager\'s context'],
            ['title' => 'View all skills', 'slug' => 'skill:viewall', 'description' => 'View all skills acquired by users of the platform'],
            ['title' => 'Create survey', 'slug' => 'survey:create', 'description' => 'Create surveys (global or inside own course)'],
            ['title' => 'Delete survey', 'slug' => 'survey:delete', 'description' => 'Delete surveys'],
            ['title' => 'Edit survey', 'slug' => 'survey:edit', 'description' => 'Edit surveys'],
            ['title' => 'Submit survey', 'slug' => 'survey:submit', 'description' => 'Submit surveys'],
            ['title' => 'View survey results', 'slug' => 'survey:viewresults', 'description' => 'View survey results'],
            ['title' => 'Comment on ticket', 'slug' => 'ticket:comment', 'description' => 'Comment on tickets'],
            ['title' => 'Manage tickets', 'slug' => 'ticket:manage', 'description' => 'Manage the tickets system'],
            ['title' => 'Report ticket', 'slug' => 'ticket:report', 'description' => 'Create tickets (most users should be able to report issues)'],
            ['title' => 'See ticket issues', 'slug' => 'ticket:seeissues', 'description' => 'See issue details for issues where user\'s involved'],
            ['title' => 'View all ticket issues', 'slug' => 'ticket:viewallissues', 'description' => 'View all issues'],
            ['title' => 'Edit tool visibility', 'slug' => 'tool:editvisibility', 'description' => 'Allow setting the visibility of a tool in a course'],
            ['title' => 'Manage URL', 'slug' => 'url:manage', 'description' => 'Manage Multi-URL configuration'],
            ['title' => 'Assign classes to URL', 'slug' => 'url:assignclass', 'description' => 'Assign classes to URL'],
            ['title' => 'Assign courses to URL', 'slug' => 'url:assigncourse', 'description' => 'Assign courses to URL'],
            ['title' => 'Assign users to URL', 'slug' => 'url:assignuser', 'description' => 'Assign users to URL'],
            ['title' => 'Assign user to course', 'slug' => 'user:assigncourse', 'description' => 'Assign a user to a course'],
            ['title' => 'Assign user to session', 'slug' => 'user:assignsession', 'description' => 'Assign a user to a session'],
            ['title' => 'Create user', 'slug' => 'user:create', 'description' => 'Create users'],
            ['title' => 'Delete user', 'slug' => 'user:delete', 'description' => 'Delete users'],
            ['title' => 'Edit user', 'slug' => 'user:edit', 'description' => 'Edit users'],
            ['title' => 'Edit user Role', 'slug' => 'user:editrole', 'description' => 'Edit user roles'],
            ['title' => 'Login as user', 'slug' => 'user:loginas', 'description' => 'Login as another user'],
            ['title' => 'Edit Course Settings', 'slug' => 'course:editsettings', 'description' => 'Edit settings of a course'],
        ];
    }

    public static function getRoles(): array
    {
        return [
            'ROLE_INVITEE' => 'INV',
            'ROLE_STUDENT' => 'STU',
            'ROLE_TEACHER' => 'TEA',
            'ROLE_ADMIN' => 'ADM',
            'ROLE_GLOBAL_ADMIN' => 'GLO',
            'ROLE_HR' => 'HRM',
            'ROLE_QUESTION_MANAGER' => 'QBM',
            'ROLE_SESSION_MANAGER' => 'SSM',
            'ROLE_STUDENT_BOSS' => 'STB',
        ];
    }

    public static function getPermissionsMapping(): array
    {
        return [
            'analytics:view' => ['INV', 'STU', 'TEA', 'ADM', 'GLO', 'HRM', 'QBM', 'SSM', 'STB'],
            'analytics:viewassigned' => ['TEA', 'ADM', 'GLO', 'HRM', 'SSM', 'STB'],
            'analytics:viewall' => ['ADM', 'GLO', 'SSM', 'STB'],
            'assignment:create' => ['TEA'],
            'assignment:delete' => ['TEA', 'ADM', 'GLO'],
            'assignment:edit' => ['TEA', 'ADM', 'GLO'],
            'assignment:grade' => ['TEA'],
            'assignment:submit' => ['STU'],
            'assignment:view' => ['INV', 'STU', 'TEA', 'ADM', 'GLO', 'HRM', 'SSM', 'STB'],
            'backup:backup' => ['TEA', 'ADM', 'GLO', 'SSM'],
            'backup:copy' => ['TEA', 'ADM', 'GLO', 'SSM'],
            'backup:restore' => ['TEA', 'ADM', 'GLO', 'SSM'],
            'badge:configurecriteria' => ['ADM', 'GLO', 'SSM'],
            'badge:create' => ['ADM', 'GLO', 'SSM'],
            'badge:edit' => ['ADM', 'GLO', 'SSM'],
            'badge:delete' => ['ADM', 'GLO', 'SSM'],
            'badge:view' => ['INV', 'STU', 'TEA', 'ADM', 'GLO', 'SSM', 'STB'],
            'calendar:create' => ['ADM', 'GLO'],
            'calendar:edit' => ['ADM', 'GLO'],
            'calendar:delete' => ['ADM', 'GLO'],
            'catalogue:view' => ['INV', 'STU', 'TEA', 'ADM', 'GLO', 'HRM', 'QBM', 'SSM', 'STB'],
            'certificate:create' => ['TEA', 'SSM'],
            'certificate:delete' => ['TEA', 'SSM'],
            'certificate:edit' => ['TEA', 'SSM'],
            'certificate:generate' => ['STU', 'TEA', 'SSM'],
            'certificate:generateall' => ['TEA', 'HRM', 'SSM'],
            'certificate:viewall' => ['TEA', 'HRM', 'SSM', 'STB'],
            'class:assigncourse' => ['TEA', 'ADM', 'GLO'],
            'class:assignsession' => ['ADM', 'GLO', 'SSM'],
            'class:assignuser' => ['ADM', 'GLO', 'SSM'],
            'class:create' => ['ADM', 'GLO', 'SSM'],
            'class:delete' => ['ADM', 'GLO', 'SSM'],
            'class:edit' => ['ADM', 'GLO', 'SSM'],
            'class:view' => ['STU', 'TEA', 'ADM', 'GLO', 'SSM'],
            'cms:create' => ['ADM', 'GLO'],
            'cms:delete' => ['ADM', 'GLO'],
            'cms:edit' => ['ADM', 'GLO'],
            'course:create' => ['TEA', 'ADM', 'GLO', 'SSM'],
            'course:delete' => ['TEA', 'ADM', 'GLO'],
            'course:downloadcoursecontent' => ['TEA', 'ADM', 'GLO', 'SSM'],
            'course:edit' => ['TEA', 'SSM'],
            'course:editall' => ['ADM', 'GLO'],
            'plugin:manage' => ['ADM', 'GLO'],
            'quiz:create' => ['TEA', 'QBM'],
            'quiz:delete' => ['TEA', 'QBM'],
            'quiz:edit' => ['TEA', 'QBM'],
            'quiz:grade' => ['TEA'],
            'quiz:viewliveresults' => ['TEA', 'SSM'],
            'quiz:managequestionbank' => ['ADM', 'GLO', 'QBM'],
            'role:create' => ['ADM', 'GLO'],
            'role:managepermissions' => ['ADM', 'GLO'],
            'session:create' => ['ADM', 'GLO', 'SSM'],
            'session:delete' => ['ADM', 'GLO', 'SSM'],
            'session:edit' => ['ADM', 'GLO', 'SSM'],
            'session:editall' => ['ADM', 'GLO', 'SSM'],
            'session:assigncourse' => ['ADM', 'GLO', 'SSM'],
            'site:editsettings' => ['ADM', 'GLO'],
            'site:maintenanceaccess' => ['ADM', 'GLO'],
            'skill:coursecompetencymanage' => ['TEA', 'ADM', 'GLO', 'HRM'],
            'skill:usercompetencyreview' => ['STU', 'TEA', 'ADM', 'GLO'],
            'skill:assign' => ['ADM', 'GLO'],
            'skill:create' => ['GLO'],
            'skill:delete' => ['GLO'],
            'skill:edit' => ['GLO'],
            'skill:view' => ['ADM', 'GLO', 'SSM', 'STB'],
            'skill:viewall' => ['ADM', 'GLO', 'SSM', 'STB'],
            'survey:create' => ['TEA'],
            'survey:delete' => ['TEA'],
            'survey:edit' => ['TEA'],
            'survey:submit' => ['INV', 'STU', 'TEA', 'ADM', 'GLO', 'SSM', 'STB'],
            'survey:viewresults' => ['TEA', 'HRM', 'SSM', 'STB'],
            'ticket:comment' => ['STU', 'TEA', 'ADM', 'GLO', 'HRM', 'QBM', 'SSM', 'STB'],
            'ticket:manage' => ['ADM', 'GLO'],
            'ticket:report' => ['STU', 'TEA', 'ADM', 'GLO', 'HRM', 'QBM', 'SSM', 'STB'],
            'ticket:seeissues' => ['STU', 'TEA', 'ADM', 'GLO', 'SSM', 'STB'],
            'ticket:viewallissues' => ['ADM', 'GLO', 'SSM', 'STB'],
            'tool:editvisibility' => ['TEA', 'ADM', 'GLO', 'SSM'],
            'url:manage' => ['GLO'],
            'url:assignclass' => ['GLO'],
            'url:assigncourse' => ['GLO'],
            'url:assignuser' => ['GLO'],
            'user:assigncourse' => ['TEA', 'ADM', 'GLO'],
            'user:assignsession' => ['ADM', 'GLO', 'SSM'],
            'user:create' => ['ADM', 'GLO'],
            'user:delete' => ['ADM', 'GLO'],
            'user:edit' => ['ADM', 'GLO'],
            'user:editrole' => ['ADM', 'GLO'],
            'user:loginas' => ['ADM', 'GLO'],
            'course:editsettings' => ['TEA', 'ADM', 'GLO'],
        ];
    }
}
