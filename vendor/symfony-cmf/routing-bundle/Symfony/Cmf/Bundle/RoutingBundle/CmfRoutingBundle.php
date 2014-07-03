<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle;

use Doctrine\Bundle\PHPCRBundle\DependencyInjection\Compiler\DoctrinePhpcrMappingsPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Cmf\Component\Routing\DependencyInjection\Compiler\RegisterRoutersPass;
use Symfony\Cmf\Component\Routing\DependencyInjection\Compiler\RegisterRouteEnhancersPass;
use Symfony\Cmf\Bundle\RoutingBundle\DependencyInjection\Compiler\SetRouterPass;

/**
 * Bundle class
 */
class CmfRoutingBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new RegisterRoutersPass());
        $container->addCompilerPass(new RegisterRouteEnhancersPass());
        $container->addCompilerPass(new SetRouterPass());

        $this->buildPhpcrCompilerPass($container);
        $this->buildOrmCompilerPass($container);
    }

    /**
     * Creates and registers compiler passes for PHPCR-ODM mapping if both the
     * phpcr-odm and the phpcr-bundle are present.
     *
     * @param ContainerBuilder $container
     */
    private function buildPhpcrCompilerPass(ContainerBuilder $container)
    {
        if (!class_exists('Doctrine\Bundle\PHPCRBundle\DependencyInjection\Compiler\DoctrinePhpcrMappingsPass')
            || !class_exists('Doctrine\ODM\PHPCR\Version')
        ) {
            return;
        }

        $container->addCompilerPass(
            $this->buildBaseCompilerPass('Doctrine\Bundle\PHPCRBundle\DependencyInjection\Compiler\DoctrinePhpcrMappingsPass', 'Doctrine\ODM\PHPCR\Mapping\Driver\XmlDriver', 'phpcr')
        );
        $container->addCompilerPass(
            DoctrinePhpcrMappingsPass::createXmlMappingDriver(
                array(
                    realpath(__DIR__.'/Resources/config/doctrine-model') => 'Symfony\Cmf\Bundle\RoutingBundle\Model',
                    realpath(__DIR__.'/Resources/config/doctrine-phpcr') => 'Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr',
                ),
                array('cmf_routing.dynamic.persistence.phpcr.manager_name'),
                'cmf_routing.backend_type_phpcr'
            )
        );
    }

    /**
     * Creates and registers compiler passes for ORM mappings if both doctrine
     * ORM and a suitable compiler pass implementation are available.
     *
     * @param ContainerBuilder $container
     */
    private function buildOrmCompilerPass(ContainerBuilder $container)
    {
        if (!class_exists('Doctrine\ORM\Version')) {
            return;
        }

        $doctrineOrmCompiler = $this->findDoctrineOrmCompiler();
        if (!$doctrineOrmCompiler) {
            return;
        }

        $container->addCompilerPass(
            $this->buildBaseCompilerPass($doctrineOrmCompiler, 'Doctrine\ORM\Mapping\Driver\XmlDriver', 'orm')
        );
        $container->addCompilerPass(
            $doctrineOrmCompiler::createXmlMappingDriver(
                array(
                    realpath(__DIR__ . '/Resources/config/doctrine-model') => 'Symfony\Cmf\Bundle\RoutingBundle\Model',
                    realpath(__DIR__ . '/Resources/config/doctrine-orm') => 'Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm',
                ),
                array('cmf_routing.dynamic.persistence.orm.manager_name'),
                'cmf_routing.backend_type_orm'
            )
        );
    }

    /**
     * Looks for a mapping compiler pass. If available, use the one from
     * DoctrineBundle (available only since DoctrineBundle 2.4 and Symfony 2.3)
     * Otherwise use the standalone one from CmfCoreBundle.
     *
     * @return boolean|string the compiler pass to use or false if no suitable
     *                        one was found
     */
    private function findDoctrineOrmCompiler()
    {
        if (class_exists('Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterMappingsPass')
            && class_exists('Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass')
        ) {
            return 'Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass';
        }

        if (class_exists('Symfony\Cmf\Bundle\CoreBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass')) {
            return 'Symfony\Cmf\Bundle\CoreBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass';
        }

        return false;
    }

    /**
     * Builds the compiler pass for the symfony core routing component. The
     * compiler pass factory method uses the SymfonyFileLocator which does
     * magic with the namespace and thus does not work here.
     *
     * @param string $compilerClass the compiler class to instantiate
     * @param string $driverClass   the xml driver class for this backend
     * @param string $type          the backend type name
     *
     * @return CompilerPassInterface
     */
    private function buildBaseCompilerPass($compilerClass, $driverClass, $type)
    {
        $arguments = array(array(realpath(__DIR__ . '/Resources/config/doctrine-base')), sprintf('.%s.xml', $type));
        $locator = new Definition('Doctrine\Common\Persistence\Mapping\Driver\DefaultFileLocator', $arguments);
        $driver = new Definition($driverClass, array($locator));

        return new $compilerClass(
            $driver,
            array('Symfony\Component\Routing'),
            array(sprintf('cmf_routing.dynamic.persistence.%s.manager_name', $type)),
            sprintf('cmf_routing.backend_type_%s', $type)
        );
    }
}
