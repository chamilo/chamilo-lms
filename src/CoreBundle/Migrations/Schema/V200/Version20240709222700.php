<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\PermissionFixtures;
use Chamilo\CoreBundle\Entity\Permission;
use Chamilo\CoreBundle\Entity\PermissionRelRole;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240709222700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Insert default data into permissions and permission_rel_roles tables';
    }

    public function up(Schema $schema): void
    {
        $permissions = PermissionFixtures::getPermissions();
        $roles = PermissionFixtures::getRoles();
        $permissionsMapping = PermissionFixtures::getPermissionsMapping();

        foreach ($permissions as $permData) {
            $permissionRepository = $this->entityManager->getRepository(Permission::class);
            $existingPermission = $permissionRepository->findOneBy(['slug' => $permData['slug']]);

            if ($existingPermission) {
                $permission = $existingPermission;
            } else {
                $permission = new Permission();
                $permission->setTitle($permData['title']);
                $permission->setSlug($permData['slug']);
                $permission->setDescription($permData['description']);

                $this->entityManager->persist($permission);
                $this->entityManager->flush();
            }

            foreach ($roles as $roleName => $roleCode) {
                if (in_array($roleCode, $permissionsMapping[$permData['slug']])) {
                    $permissionRelRoleRepository = $this->entityManager->getRepository(PermissionRelRole::class);
                    $existingRelation = $permissionRelRoleRepository->findOneBy([
                        'permission' => $permission,
                        'roleCode' => $roleName
                    ]);

                    if ($existingRelation) {
                        continue;
                    }

                    $permissionRelRole = new PermissionRelRole();
                    $permissionRelRole->setPermission($permission);
                    $permissionRelRole->setRoleCode($roleName);
                    $permissionRelRole->setChangeable(true);
                    $permissionRelRole->setUpdatedAt(new \DateTime());

                    $this->entityManager->persist($permissionRelRole);
                    $this->entityManager->flush();
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM permission_rel_roles');
        $this->addSql('DELETE FROM permissions');
    }
}
