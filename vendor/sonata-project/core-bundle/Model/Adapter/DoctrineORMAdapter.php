<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\Model\Adapter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * This is a port of the DoctrineORMAdminBundle / ModelManager class
 *
 * @package Sonata\CoreBundle\Model\Adapter
 */
class DoctrineORMAdapter implements AdapterInterface
{
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedIdentifier($entity)
    {
        if (is_scalar($entity)) {
            throw new \RunTimeException('Invalid argument, object or null required');
        }

        if (!$entity) {
            return null;
        }

        $manager = $this->registry->getManagerForClass(get_class($entity));

        if (!$manager instanceof EntityManagerInterface) {
            return null;
        }

        if (!$manager->getUnitOfWork()->isInIdentityMap($entity)) {
            return null;
        }

        return implode(self::ID_SEPARATOR, $manager->getUnitOfWork()->getEntityIdentifier($entity));
    }

    /**
     * {@inheritDoc}
     *
     * The ORM implementation does nothing special but you still should use
     * this method when using the id in a URL to allow for future improvements.
     */
    public function getUrlsafeIdentifier($entity)
    {
        return $this->getNormalizedIdentifier($entity);
    }
}
