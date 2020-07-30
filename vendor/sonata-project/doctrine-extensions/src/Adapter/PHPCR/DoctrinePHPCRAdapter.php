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

namespace Sonata\Doctrine\Adapter\PHPCR;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Persistence\ManagerRegistry;
use Sonata\Doctrine\Adapter\AdapterInterface;

/**
 * This is a port of the DoctrineORMAdminBundle / ModelManager class.
 */
class DoctrinePHPCRAdapter implements AdapterInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getNormalizedIdentifier($document)
    {
        if (null === $document) {
            return null;
        }

        if (!\is_object($document)) {
            throw new \RuntimeException('Invalid argument, object or null required');
        }

        $manager = $this->registry->getManagerForClass($document);

        if (!$manager instanceof DocumentManager) {
            return null;
        }

        if (!$manager->contains($document)) {
            return null;
        }

        $class = $manager->getClassMetadata(\get_class($document));

        return $class->getIdentifierValue($document);
    }

    /**
     * Currently only the leading slash is removed.
     *
     * TODO: do we also have to encode certain characters like spaces or does that happen automatically?
     *
     * {@inheritdoc}
     */
    public function getUrlSafeIdentifier($document)
    {
        $id = $this->getNormalizedIdentifier($document);

        if (null !== $id) {
            return substr($id, 1);
        }

        return null;
    }
}

class_exists(\Sonata\CoreBundle\Model\Adapter\DoctrinePHPCRAdapter::class);
