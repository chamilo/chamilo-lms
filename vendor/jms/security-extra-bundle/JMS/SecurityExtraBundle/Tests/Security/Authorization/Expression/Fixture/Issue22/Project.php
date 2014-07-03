<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Authorization\Expression\Fixture\Issue22;

class Project
{
    public $company;

    public function __construct()
    {
        $this->company = new \stdClass;
    }

    public function getCompany()
    {
        return $this->company;
    }
}