<?php

namespace JMS\SecurityExtraBundle\Twig;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use Symfony\Component\Security\Core\SecurityContextInterface;

class SecurityExtension extends \Twig_Extension
{
    private $context;

    public function __construct(SecurityContextInterface $context)
    {
        $this->context = $context;
    }

    public function getFunctions()
    {
        return array(
            'is_expr_granted' => new \Twig_Function_Method($this, 'isExprGranted'),
        );
    }

    public function isExprGranted($expr, $object = null)
    {
        return $this->context->isGranted(array(new Expression($expr)), $object);
    }

    public function getName()
    {
        return 'jms_security_extra';
    }
}
