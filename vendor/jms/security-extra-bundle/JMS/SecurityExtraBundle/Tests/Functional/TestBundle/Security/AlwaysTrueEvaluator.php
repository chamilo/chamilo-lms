<?php

namespace JMS\SecurityExtraBundle\Tests\Functional\TestBundle\Security;

class AlwaysTrueEvaluator
{
    private $nbCalls = 0;

    public function getNbCalls()
    {
        return $this->nbCalls;
    }

    public function hasAccess()
    {
        $this->nbCalls += 1;

        return true;
    }
}