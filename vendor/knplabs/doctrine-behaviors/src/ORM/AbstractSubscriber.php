<?php

/**
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\ORM;

use Doctrine\Common\EventSubscriber;
use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;

abstract class AbstractSubscriber implements EventSubscriber
{
    private $classAnalyser;
    protected $isRecursive;

    public function __construct(ClassAnalyzer $classAnalyser, $isRecursive)
    {
        $this->classAnalyser = $classAnalyser;
        $this->isRecursive   = (bool) $isRecursive;
    }

    protected function getClassAnalyzer()
    {
        return $this->classAnalyser;
    }

    abstract public function getSubscribedEvents();
}
