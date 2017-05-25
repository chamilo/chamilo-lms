<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\ORM\Timestampable;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;

use Knp\DoctrineBehaviors\ORM\AbstractSubscriber;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\Events,
    Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Timestampable subscriber.
 *
 * Adds mapping to the timestampable entites.
 */
class TimestampableSubscriber extends AbstractSubscriber
{
    private $timestampableTrait;
    private $dbFieldType;

    public function __construct(ClassAnalyzer $classAnalyzer, $isRecursive, $timestampableTrait, $dbFieldType)
    {
        parent::__construct($classAnalyzer, $isRecursive);

        $this->timestampableTrait = $timestampableTrait;
        $this->dbFieldType = $dbFieldType;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (null === $classMetadata->reflClass) {
            return;
        }

        if ($this->isTimestampable($classMetadata)) {
            if ($this->getClassAnalyzer()->hasMethod($classMetadata->reflClass, 'updateTimestamps')) {
                $classMetadata->addLifecycleCallback('updateTimestamps', Events::prePersist);
                $classMetadata->addLifecycleCallback('updateTimestamps', Events::preUpdate);
            }

            foreach (array('createdAt', 'updatedAt') as $field) {
                if (!$classMetadata->hasField($field)) {
                    $classMetadata->mapField(array(
                        'fieldName' => $field,
                        'type'      => $this->dbFieldType,
                        'nullable'  => true
                    ));
                }
            }
        }
    }

    public function getSubscribedEvents()
    {
        return [Events::loadClassMetadata];
    }

    /**
     * Checks if entity is timestampable
     *
     * @param ClassMetadata $classMetadata The metadata
     *
     * @return Boolean
     */
    private function isTimestampable(ClassMetadata $classMetadata)
    {
        return $this->getClassAnalyzer()->hasTrait(
            $classMetadata->reflClass,
            $this->timestampableTrait,
            $this->isRecursive
        );
    }
}
