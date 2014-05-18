<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            // Vendor specifics bundles
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        $bundles[] = new ChamiloLMS\CoreBundle\ChamiloLMSCoreBundle();
        $bundles[] = new Braincrafted\Bundle\BootstrapBundle\BraincraftedBootstrapBundle();

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

    public function getLogDir()
    {
        return $this->rootDir.'/../logs/'.$this->environment.'/logs/';
    }

    public function getCacheDir()
    {
        return $this->rootDir.'/../data/temp/'.$this->environment.'/cache/';
    }

    // Custom

    public function getRealRootDir()
    {
        return realpath($this->rootDir.'/../').'/';
    }

    public function getDataDir()
    {
        return $this->getRealRootDir().'/data/';
    }

    public function getConfigDir()
    {
        return $this->getRealRootDir().'/config/';
    }
}
