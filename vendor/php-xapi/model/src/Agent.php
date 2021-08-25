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
 * An individual Agent of an xAPI {@link Statement}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class Agent extends Actor
{
    public function __construct(InverseFunctionalIdentifier $iri, string $name = null)
    {
        parent::__construct($iri, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function equals(StatementObject $actor): bool
    {
        if (!parent::equals($actor)) {
            return false;
        }

        return true;
    }
}
