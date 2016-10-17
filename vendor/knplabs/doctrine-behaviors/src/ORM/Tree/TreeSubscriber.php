<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\ORM\Tree;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;

use Knp\DoctrineBehaviors\ORM\AbstractSubscriber;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\Events,
    Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Tree subscriber.
 *
 * Adds mapping to the tree entities.
 */
class TreeSubscriber extends AbstractSubscriber
{
    private $nodeTrait;

    public function __construct(ClassAnalyzer $classAnalyzer, $isRecursive, $nodeTrait)
    {
        parent::__construct($classAnalyzer, $isRecursive);

        $this->nodeTrait = $nodeTrait;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (null === $classMetadata->reflClass) {
            return;
        }

        if ($this->isTreeNode($classMetadata)) {

            if (!$classMetadata->hasField('materializedPath')) {
                $classMetadata->mapField(array(
                    'fieldName' => 'materializedPath',
                    'type'      => 'string',
                    'length'    => 255
                ));
            }
        }
    }

    public function getSubscribedEvents()
    {
        return [Events::loadClassMetadata];
    }

    /**
     * Checks if entity is a tree
     *
     * @param ClassMetadata $classMetadata The metadata
     *
     * @return Boolean
     */
    private function isTreeNode(ClassMetadata $classMetadata)
    {
        return $this->getClassAnalyzer()->hasTrait(
            $classMetadata->reflClass,
            $this->nodeTrait,
            $this->isRecursive
        );
    }
}
