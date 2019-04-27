<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\ORM\SoftDeletable;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;

use Knp\DoctrineBehaviors\ORM\AbstractSubscriber;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\Events;

/**
 * SoftDeletable Doctrine2 subscriber.
 *
 * Listens to onFlush event and marks SoftDeletable entities
 * as deleted instead of really removing them.
 */
class SoftDeletableSubscriber extends AbstractSubscriber
{
    private $softDeletableTrait;

    public function __construct(ClassAnalyzer $classAnalyzer, $isRecursive, $softDeletableTrait)
    {
        parent::__construct($classAnalyzer, $isRecursive);

        $this->softDeletableTrait = $softDeletableTrait;
    }

    /**
     * Listens to onFlush event.
     *
     * @param OnFlushEventArgs $args The event arguments
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $classMetadata = $em->getClassMetadata(get_class($entity));
            if ($this->isSoftDeletable($classMetadata)) {
                $oldValue = $entity->getDeletedAt();

                $entity->delete();
                $em->persist($entity);

                $uow->propertyChanged($entity, 'deletedAt', $oldValue, $entity->getDeletedAt());
                $uow->scheduleExtraUpdate($entity, [
                    'deletedAt' => [$oldValue, $entity->getDeletedAt()]
                ]);
            }
        }
    }

    /**
     * Checks if entity is softDeletable
     *
     * @param ClassMetadata $classMetadata The metadata
     *
     * @return Boolean
     */
    private function isSoftDeletable(ClassMetadata $classMetadata)
    {
        return $this->getClassAnalyzer()->hasTrait($classMetadata->reflClass, $this->softDeletableTrait, $this->isRecursive);
    }

    /**
     * Returns list of events, that this subscriber is listening to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [Events::onFlush, Events::loadClassMetadata];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (null === $classMetadata->reflClass) {
            return;
        }

        if ($this->isSoftDeletable($classMetadata)) {

            if (!$classMetadata->hasField('deletedAt')) {
                $classMetadata->mapField(array(
                    'fieldName' => 'deletedAt',
                    'type'      => 'datetime',
                    'nullable'  => true
                ));
            }
        }
    }
}
