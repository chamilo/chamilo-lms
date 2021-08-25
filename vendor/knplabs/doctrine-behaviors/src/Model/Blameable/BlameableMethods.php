<?php

/**
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\DoctrineBehaviors\Model\Blameable;

/**
 * Blameable trait.
 *
 * Should be used inside entity where you need to track which user created or updated it
 */
trait BlameableMethods
{
    /**
     * @param mixed the user representation
     * @return $this
     */
    public function setCreatedBy($user)
    {
        $this->createdBy = $user;

        return $this;
    }

    /**
     * @param mixed the user representation
     * @return $this
     */
    public function setUpdatedBy($user)
    {
        $this->updatedBy = $user;

        return $this;
    }

    /**
     * @param mixed the user representation
     * @return $this
     */
    public function setDeletedBy($user)
    {
        $this->deletedBy = $user;

        return $this;
    }

    /**
     * @return mixed the user who created entity
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @return mixed the user who last updated entity
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * @return mixed the user who removed entity
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }


    public function isBlameable()
    {
        return true;
    }
}
