<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SecurityExtraBundle\Metadata;

use Metadata\MethodMetadata as BaseMethodMetadata;

/**
 * Contains method metadata information
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class MethodMetadata extends BaseMethodMetadata
{
    public $roles = array();
    public $paramPermissions = array();
    public $returnPermissions = array();
    public $runAsRoles = array();
    public $satisfiesParentSecurityPolicy = false;

    /**
     * Adds a parameter restriction
     *
     * @param integer $index       0-based
     * @param array   $permissions
     */
    public function addParamPermissions($index, array $permissions)
    {
        $this->paramPermissions[$index] = $permissions;
    }

    public function isDeclaredOnInterface()
    {
        foreach ($this->reflection->getDeclaringClass()->getInterfaces() as $interface) {
            if ($interface->hasMethod($this->name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * This allows to merge in metadata from an interface
     *
     * @param  MethodMetadata $method
     * @return void
     */
    public function merge(MethodMetadata $method)
    {
        if (!$this->roles) {
            $this->roles = $method->roles;
        }

        if (!$this->returnPermissions) {
            $this->returnPermissions = $method->returnPermissions;
        }

        if (!$this->runAsRoles) {
            $this->runAsRoles = $method->runAsRoles;
        }

        foreach ($method->paramPermissions as $index => $permissions) {
            if (!isset($this->paramPermissions[$index])) {
                $this->paramPermissions[$index] = $permissions;
            }
        }
    }

    public function serialize()
    {
        return serialize(array(
            parent::serialize(),
            $this->roles, $this->paramPermissions, $this->returnPermissions,
            $this->runAsRoles, $this->satisfiesParentSecurityPolicy,
        ));
    }

    public function unserialize($str)
    {
        list($parentStr,
            $this->roles, $this->paramPermissions, $this->returnPermissions,
            $this->runAsRoles, $this->satisfiesParentSecurityPolicy
        ) = unserialize($str);

        parent::unserialize($parentStr);
    }
}
