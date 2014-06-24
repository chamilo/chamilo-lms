<?php

namespace JMS\SecurityExtraBundle\Security\Authorization;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * An introspectable access decision manager.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RememberingAccessDecisionManager implements AccessDecisionManagerInterface
{
    private $delegate;
    private $lastDecisionCall;

    public function __construct(AccessDecisionManagerInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    public function getLastDecisionCall()
    {
        return $this->lastDecisionCall;
    }

    /**
     * Decides whether the access is possible or not.
     *
     * @param TokenInterface $token      A TokenInterface instance
     * @param array          $attributes An array of attributes associated with the method being invoked
     * @param object         $object     The object to secure
     *
     * @return Boolean true if the access is granted, false otherwise
     */
    public function decide(TokenInterface $token, array $attributes, $object = null)
    {
        $this->lastDecisionCall = array($token, $attributes, $object);

        return $this->delegate->decide($token, $attributes, $object);
    }

    /**
     * Checks if the access decision manager supports the given attribute.
     *
     * @param string $attribute An attribute
     *
     * @return Boolean true if this decision manager supports the attribute, false otherwise
     */
    public function supportsAttribute($attribute)
    {
        return $this->delegate->supportsAttribute($attribute);
    }

    /**
     * Checks if the access decision manager supports the given class.
     *
     * @param string $class A class name
     *
     * @return true if this decision manager can process the class
     */
    public function supportsClass($class)
    {
        return $this->delegate->supportsClass($class);
    }
}