<?php

namespace JMS\DiExtraBundle\Tests\Metadata\Driver\Fixture;

use Symfony\Component\Form\AbstractType;
use JMS\DiExtraBundle as DI;

/**
 * @DI\Annotation\FormType("foo")
 *
 * @author johannes
 */
class SignUpType extends AbstractType
{
    public function getName()
    {
        return 'sign_up';
    }
}
