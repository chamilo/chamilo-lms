<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Doctrine\Adapter\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Sonata\Doctrine\Adapter\AdapterInterface;

/**
 * This is a port of the DoctrineORMAdminBundle / ModelManager class.
 */
class DoctrineORMAdapter implements AdapterInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getNormalizedIdentifier($entity)
    {
        if (null === $entity) {
            return null;
        }

        if (!\is_object($entity)) {
            throw new \RuntimeException('Invalid argument, object or null required');
        }

        $manager = $this->registry->getManagerForClass(\get_class($entity));

        if (!$manager instanceof EntityManagerInterface) {
            return null;
        }

        if (!$manager->getUnitOfWork()->isInIdentityMap($entity)) {
            return null;
        }

        return implode(self::ID_SEPARATOR, $manager->getUnitOfWork()->getEntityIdentifier($entity));
    }

    /**
     * {@inheritdoc}
     *
     * The ORM implementation does nothing special but you still should use
     * this method when using the id in a URL to allow for future improvements.
     */
    public function getUrlSafeIdentifier($entity)
    {
        return $this->getNormalizedIdentifier($entity);
    }
}

class_exists(\Sonata\CoreBundle\Model\Adapter\DoctrineORMAdapter::class);
