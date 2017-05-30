<?php

namespace Knp\DoctrineBehaviors\Bundle;

use Knp\DoctrineBehaviors\Bundle\DependencyInjection\DoctrineBehaviorsExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DoctrineBehaviorsBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new DoctrineBehaviorsExtension();
    }
}
