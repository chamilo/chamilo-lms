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
 * Filter to apply on GET requests to the statements API.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class StatementsFilter
{
    private $filter = array();

    /**
     * Filters by an Agent or an identified Group.
     */
    public function byActor(Actor $actor): self
    {
        $this->filter['agent'] = $actor;

        return $this;
    }

    /**
     * Filters by a verb.
     */
    public function byVerb(Verb $verb): self
    {
        $this->filter['verb'] = $verb->getId()->getValue();

        return $this;
    }

    /**
     * Filter by an Activity.
     */
    public function byActivity(Activity $activity): self
    {
        $this->filter['activity'] = $activity->getId()->getValue();

        return $this;
    }

    /**
     * Filters for Statements matching the given registration id.
     */
    public function byRegistration(string $registration): self
    {
        $this->filter['registration'] = $registration;

        return $this;
    }

    /**
     * Applies the Activity filter to Sub-Statements.
     */
    public function enableRelatedActivityFilter(): self
    {
        $this->filter['related_activities'] = 'true';

        return $this;
    }

    /**
     * Don't apply the Activity filter to Sub-Statements.
     */
    public function disableRelatedActivityFilter(): self
    {
        $this->filter['related_activities'] = 'false';

        return $this;
    }

    /**
     * Applies the Agent filter to Sub-Statements.
     */
    public function enableRelatedAgentFilter(): self
    {
        $this->filter['related_agents'] = 'true';

        return $this;
    }

    /**
     * Don't apply the Agent filter to Sub-Statements.
     */
    public function disableRelatedAgentFilter(): self
    {
        $this->filter['related_agents'] = 'false';

        return $this;
    }

    /**
     * Filters for Statements stored since the specified timestamp (exclusive).
     */
    public function since(\DateTime $timestamp): self
    {
        $this->filter['since'] = $timestamp->format('c');

        return $this;
    }

    /**
     * Filters for Statements stored at or before the specified timestamp.
     */
    public function until(\DateTime $timestamp): self
    {
        $this->filter['until'] = $timestamp->format('c');

        return $this;
    }

    /**
     * Sets the maximum number of Statements to return. The server side sets
     * the maximum number of results when this value is not set or when it is 0.
     *
     * @throws \InvalidArgumentException if the limit is not a non-negative
     *                                   integer
     */
    public function limit(int $limit): self
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('Limit must be a non-negative integer');
        }

        $this->filter['limit'] = $limit;

        return $this;
    }

    /**
     * Return statements in ascending order of stored time.
     */
    public function ascending(): self
    {
        $this->filter['ascending'] = 'true';

        return $this;
    }

    /**
     *Return statements in descending order of stored time (the default behavior).
     */
    public function descending(): self
    {
        $this->filter['ascending'] = 'false';

        return $this;
    }

    /**
     * Returns the generated filter.
     */
    public function getFilter(): array
    {
        return $this->filter;
    }
}
