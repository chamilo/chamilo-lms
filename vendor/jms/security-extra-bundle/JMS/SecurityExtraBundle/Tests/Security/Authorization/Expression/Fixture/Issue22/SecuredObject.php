<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression\Fixture\Issue22;

class SecuredObject
{
    /**
     * @PreAuthorize("hasPermission(#project.getCompany(), 'OPERATOR')")
     */
    public function delete(Project $project)
    {
        return true;
    }
}
