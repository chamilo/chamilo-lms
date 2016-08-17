<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\Model\SoftDeletable;

/**
 * SoftDeletable trait.
 *
 * Should be used inside entity, that needs to be self-deleted.
 */
trait SoftDeletableMethods
{
    /**
     * Marks entity as deleted.
     */
    public function delete()
    {
        $this->deletedAt = new \DateTime();
    }

    /**
     * Restore entity by undeleting it
     */
    public function restore()
    {
        $this->deletedAt = null;
    }

    /**
     * Checks whether the entity has been deleted.
     *
     * @return Boolean
     */
    public function isDeleted()
    {
        if (null !== $this->deletedAt) {
            return $this->deletedAt <= (new \DateTime());
        }

        return false;
    }

    /**
     * Checks whether the entity will be deleted.
     *
     * @return Boolean
     */
    public function willBeDeleted(\DateTime $at = null)
    {
        if ($this->deletedAt === null) {

            return false;
        }
        if ($at === null) {

            return true;
        }

        return $this->deletedAt <= $at;
    }

    /**
     * Returns date on which entity was been deleted.
     *
     * @return DateTime|null
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set the delete date to given date.
     *
     * @param DateTime $date
     * @param               Object
     *
     * @return $this
     */
    public function setDeletedAt(\DateTime $date)
    {
        $this->deletedAt = $date;

        return $this;
    }
}
