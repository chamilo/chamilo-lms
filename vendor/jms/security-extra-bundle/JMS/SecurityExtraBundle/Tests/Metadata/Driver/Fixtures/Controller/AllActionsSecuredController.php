<?php

namespace JMS\SecurityExtraBundle\Tests\Metadata\Driver\Fixtures\Controller;

use JMS\SecurityExtraBundle\Annotation\PreAuthorize;

/**
 * @PreAuthorize("hasRole('foo')")
 */
class AllActionsSecuredController
{
    public function fooAction() { }
    public function barAction() { }

    /** @PreAuthorize("hasRole('bar')") */
    public function bazAction() { }

    protected function getFoo() { }
}