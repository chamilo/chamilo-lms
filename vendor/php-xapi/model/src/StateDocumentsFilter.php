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
 * Filter to apply on GET requests to the states API.
 *
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
class StateDocumentsFilter
{
    /**
     * @var array The generated filter
     */
    private $filter = array();

    /**
     * Filter by an Activity.
     */
    public function byActivity(Activity $activity): self
    {
        $this->filter['activity'] = $activity->getId()->getValue();

        return $this;
    }

    /**
     * Filters by an Agent.
     */
    public function byAgent(Agent $agent): self
    {
        $this->filter['agent'] = $agent;

        return $this;
    }

    /**
     * Filters for State documents matching the given registration id.
     */
    public function byRegistration(string $registration): self
    {
        $this->filter['registration'] = $registration;

        return $this;
    }

    /**
     * Filters for State documents stored since the specified timestamp (exclusive).
     */
    public function since(\DateTime $timestamp): self
    {
        $this->filter['since'] = $timestamp->format('c');

        return $this;
    }

    /**
     * Returns the generated filter.
     *
     * @return array The filter
     */
    public function getFilter(): array
    {
        return $this->filter;
    }
}
