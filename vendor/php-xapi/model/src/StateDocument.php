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
 * A document associated to an activity provider state.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StateDocument extends Document
{
    private $state;

    public function __construct(State $state, DocumentData $data)
    {
        parent::__construct($data);

        $this->state = $state;
    }

    /**
     * Returns the document's {@link State}.
     */
    public function getState(): State
    {
        return $this->state;
    }
}
