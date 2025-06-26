<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo;

use Chamilo\CoreBundle\Utils\ChamiloUtil;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * @return string
     */
    public function getConfigurationFile()
    {
        return $this->getProjectDir().'/config/configuration.php';
    }

    public function setApi(array $configuration): void
    {
        new ChamiloUtil($configuration);
    }

    /**
     * Check if system is installed
     * Checks the APP_INSTALLED env value.
     */
    public function isInstalled(): bool
    {
        return !empty($this->getContainer()->getParameter('installed'));
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');
        $container->import('../config/{services}.yaml');
        $container->import('../config/{services}_'.$this->environment.'.yaml');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}/*.yaml');
        $routes->import('../config/{routes}.yaml');
    }
}
