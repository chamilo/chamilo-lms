<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Security;

use Sonata\AdminBundle\Admin\Pool;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EditableRolesBuilder
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $rolesHierarchy;

    /**
     * @param TokenStorageInterface         $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param Pool                          $pool
     * @param array                         $rolesHierarchy
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker, Pool $pool, array $rolesHierarchy = [])
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->pool = $pool;
        $this->rolesHierarchy = $rolesHierarchy;
    }

    /*
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @param string|bool|null $domain
     * @param bool             $expanded
     *
     * @return array
     */
    public function getRoles($domain = false, $expanded = true)
    {
        $roles = [];

        if (!$this->tokenStorage->getToken()) {
            return $roles;
        }

        $this->iterateAdminRoles(function ($role, $isMaster) use ($domain, &$roles): void {
            if ($isMaster) {
                // if the user has the MASTER permission, allow to grant access the admin roles to other users
                $roles[$role] = $this->translateRole($role, $domain);
            }
        });

        $isMaster = $this->authorizationChecker->isGranted(
            $this->pool->getOption('role_super_admin', 'ROLE_SUPER_ADMIN')
        );

        // get roles from the service container
        foreach ($this->rolesHierarchy as $name => $rolesHierarchy) {
            if ($this->authorizationChecker->isGranted($name) || $isMaster) {
                $roles[$name] = $this->translateRole($name, $domain);
                if ($expanded) {
                    $result = array_map([$this, 'translateRole'], $rolesHierarchy, array_fill(0, count($rolesHierarchy), $domain));
                    $roles[$name] .= ': '.implode(', ', $result);
                }
                foreach ($rolesHierarchy as $role) {
                    if (!isset($roles[$role])) {
                        $roles[$role] = $this->translateRole($role, $domain);
                    }
                }
            }
        }

        return $roles;
    }

    /**
     * @param string|bool|null $domain
     *
     * @return array
     */
    public function getRolesReadOnly($domain = false)
    {
        $rolesReadOnly = [];

        if (!$this->tokenStorage->getToken()) {
            return $rolesReadOnly;
        }

        $this->iterateAdminRoles(function ($role, $isMaster) use ($domain, &$rolesReadOnly): void {
            if (!$isMaster && $this->authorizationChecker->isGranted($role)) {
                // although the user has no MASTER permission, allow the currently logged in user to view the role
                $rolesReadOnly[$role] = $this->translateRole($role, $domain);
            }
        });

        return $rolesReadOnly;
    }

    private function iterateAdminRoles(callable $func): void
    {
        // get roles from the Admin classes
        foreach ($this->pool->getAdminServiceIds() as $id) {
            try {
                $admin = $this->pool->getInstance($id);
            } catch (\Exception $e) {
                continue;
            }

            $isMaster = $admin->isGranted('MASTER');
            $securityHandler = $admin->getSecurityHandler();
            // TODO get the base role from the admin or security handler
            $baseRole = $securityHandler->getBaseRole($admin);

            if (0 == strlen($baseRole)) { // the security handler related to the admin does not provide a valid string
                continue;
            }

            foreach ($admin->getSecurityInformation() as $role => $permissions) {
                $role = sprintf($baseRole, $role);
                call_user_func($func, $role, $isMaster, $permissions);
            }
        }
    }

    /*
     * @param string $role
     * @param string|bool|null $domain
     *
     * @return string
     */
    private function translateRole($role, $domain)
    {
        // translation domain is false, do not translate it,
        // null is fallback to message domain
        if (false === $domain || !isset($this->translator)) {
            return $role;
        }

        return $this->translator->trans($role, [], $domain);
    }
}
