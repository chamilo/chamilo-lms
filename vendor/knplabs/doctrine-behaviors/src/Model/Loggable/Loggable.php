<?php

/**
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\Model\Loggable;

/**
 * Loggable trait.
 *
 * Should be used inside entity where you need to track modifications log
 */
trait Loggable
{
    /**
     * @return string some log informations
     */
    public function getUpdateLogMessage(array $changeSets = [])
    {
        $message = [];
        foreach ($changeSets as $property => $changeSet) {
            for ($i = 0, $s = sizeof($changeSet); $i < $s; $i++) {
                if ($changeSet[$i] instanceof \DateTime) {
                    $changeSet[$i] = $changeSet[$i]->format("Y-m-d H:i:s");
                }
            }

            if ($changeSet[0] != $changeSet[1]) {
                $message[] = sprintf(
                    '%s #%d : property "%s" changed from "%s" to "%s"',
                    __CLASS__,
                    $this->getId(),
                    $property,
                    !is_array($changeSet[0]) ? $changeSet[0] : "an array",
                    !is_array($changeSet[1]) ? $changeSet[1] : "an array"
                );
            }
        }

        return implode("\n", $message);
    }

    public function getCreateLogMessage()
    {
        return sprintf('%s #%d created', __CLASS__, $this->getId());
    }

    public function getRemoveLogMessage()
    {
        return sprintf('%s #%d removed', __CLASS__, $this->getId());
    }
}
