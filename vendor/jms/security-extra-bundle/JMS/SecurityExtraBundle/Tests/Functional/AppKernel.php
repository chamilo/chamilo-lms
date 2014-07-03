<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

require_once __DIR__.'/../../vendor/autoload.php';

use Symfony\Bundle\SecurityBundle\Tests\Functional\Bundle\FormLoginBundle\FormLoginBundle;
use JMS\SecurityExtraBundle\Tests\Functional\TestBundle\TestBundle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    private $config;

    public function __construct($config)
    {
        parent::__construct('test', true);

        $fs = new Filesystem();
        if (!$fs->isAbsolutePath($config)) {
            $config = __DIR__.'/config/'.$config;
        }

        if (!file_exists($config)) {
            throw new \RuntimeException(sprintf('The config file "%s" does not exist.', $config));
        }

        $this->config = $config;
    }

    public function registerBundles()
    {
        return array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new TestBundle(),
            new FormLoginBundle(),
            new \Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new \JMS\AopBundle\JMSAopBundle(),
            new \JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new \JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->config);
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/JMSSecurityExtraBundle/'.sha1($this->config);
    }

    protected function getContainerClass()
    {
        return parent::getContainerClass().sha1($this->config);
    }

    public function serialize()
    {
        return $this->config;
    }

    public function unserialize($str)
    {
        $this->__construct($str);
    }
}
