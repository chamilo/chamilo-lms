<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingBundle\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Abstract class for doctrine based content repository and route provider
 * implementations.
 *
 * @author Uwe Jäger
 * @author David Buchmann <mail@davidbu.ch>
 */
abstract class DoctrineProvider
{
    /**
     * If this is null, the manager registry will return the default manager.
     *
     * @var string|null Name of object manager to use
     */
    protected $managerName;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * Class name of the object class to find, null for PHPCR-ODM as it can
     * determine the class on its own.
     *
     * @var string|null
     */
    protected $className;

    /**
     * Limit to apply when calling getRoutesByNames() with null
     *
     * @var integer|null
     */
    protected $routeCollectionLimit;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string          $className
     */
    public function __construct(ManagerRegistry $managerRegistry, $className = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->className = $className;
    }

    /**
     * Set the object manager name to use for this loader. If not set, the
     * default manager as decided by the manager registry will be used.
     *
     * @param string|null $managerName
     */
    public function setManagerName($managerName)
    {
        $this->managerName = $managerName;
    }

    /**
     * Set the limit to apply when calling getAllRoutes().
     *
     * Setting the limit to null means no limit is applied.
     *
     * @param integer|null $routeCollectionLimit
     */
    public function setRouteCollectionLimit($routeCollectionLimit = null)
    {
        $this->routeCollectionLimit = $routeCollectionLimit;
    }

    /**
     * Get the object manager named $managerName from the registry.
     *
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->managerRegistry->getManager($this->managerName);
    }
}
