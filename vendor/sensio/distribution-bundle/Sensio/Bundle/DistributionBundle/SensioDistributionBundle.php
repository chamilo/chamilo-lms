<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\DistributionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Sensio\Bundle\DistributionBundle\Configurator\Step\DoctrineStep;
use Sensio\Bundle\DistributionBundle\Configurator\Step\SecretStep;

/**
 * SensioDistributionBundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class SensioDistributionBundle extends Bundle
{
    public function boot()
    {
        $configurator = $this->container->get('sensio_distribution.webconfigurator');
        $configurator->addStep(new DoctrineStep($configurator->getParameters()));
        $configurator->addStep(new SecretStep($configurator->getParameters()));
    }
}
