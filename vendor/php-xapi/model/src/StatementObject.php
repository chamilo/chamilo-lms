<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

/**
 * The object of a {@link Statement}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class StatementObject
{
    /**
     * Checks if another object is equal.
     *
     * Two objects are equal if and only if all of their properties are equal.
     */
    public function equals(StatementObject $object): bool
    {
        return get_class($this) === get_class($object);
    }
}
