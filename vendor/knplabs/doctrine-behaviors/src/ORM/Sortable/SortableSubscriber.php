<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\ORM\Sortable;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;

use Knp\DoctrineBehaviors\ORM\AbstractSubscriber;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\Events,
    Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Sortable subscriber.
 *
 * Adds mapping to the sortable entities.
 */
class SortableSubscriber extends AbstractSubscriber
{
    private $sortableTrait;

    public function __construct(ClassAnalyzer $classAnalyzer, $isRecursive, $sortableTrait)
    {
        parent::__construct($classAnalyzer, $isRecursive);

        $this->sortableTrait = $sortableTrait;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (null === $classMetadata->reflClass) {
            return;
        }

        if ($this->isSortable($classMetadata)) {

            if (!$classMetadata->hasField('sort')) {
                $classMetadata->mapField(array(
                    'fieldName' => 'sort',
                    'type'      => 'integer'
                ));
            }
        }
    }

    public function getSubscribedEvents()
    {
        return [Events::loadClassMetadata];
    }

    /**
     * Checks if entity is a sortable
     *
     * @param ClassMetadata $classMetadata The metadata
     *
     * @return Boolean
     */
    private function isSortable(ClassMetadata $classMetadata)
    {
        return $this->getClassAnalyzer()->hasTrait(
            $classMetadata->reflClass,
            $this->sortableTrait,
            $this->isRecursive
        );
    }
}
