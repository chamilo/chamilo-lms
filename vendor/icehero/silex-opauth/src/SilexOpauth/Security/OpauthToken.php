<?php

namespace SilexOpauth\Security;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * @author Rafal Lindemann
 *  */
class OpauthToken extends AbstractToken
{

    public function __construct(OpauthResult $result, array $roles = array())
    {
        parent::__construct($roles);

        // If the user has roles, consider it authenticated
        $this->setAuthenticated(count($roles) > 0);
        $this->setAttribute('opauth', $result);
    }


    public function getCredentials()
    {
        return '';
    }


    /** @return OpauthResult */
    public function getOpauthResult()
    {
        return $this->getAttribute('opauth');
    }


}