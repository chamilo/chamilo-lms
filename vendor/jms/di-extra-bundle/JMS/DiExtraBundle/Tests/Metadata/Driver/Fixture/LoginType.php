<?php

namespace JMS\DiExtraBundle\Tests\Metadata\Driver\Fixture;

use Security\SecurityContext;
use Symfony\Component\Form\AbstractType;
use JMS\DiExtraBundle as DI; // Use this alias in order to not have this class picked up by the finder

/**
 * @DI\Annotation\FormType
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class LoginType extends AbstractType
{
    private $securityContext;

    public function __construct(SecurityContext $context)
    {
        $this->securityContext = $context;
    }

    public function getName()
    {
        return 'login';
    }
}
