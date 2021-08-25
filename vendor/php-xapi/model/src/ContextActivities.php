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
 * xAPI context activities.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class ContextActivities
{
    private $parentActivities;
    private $groupingActivities;
    private $categoryActivities;
    private $otherActivities;

    /**
     * @param Activity[]|null $parentActivities
     * @param Activity[]|null $groupingActivities
     * @param Activity[]|null $categoryActivities
     * @param Activity[]|null $otherActivities
     */
    public function __construct(array $parentActivities = null, array $groupingActivities = null, array $categoryActivities = null, array $otherActivities = null)
    {
        $this->parentActivities = $parentActivities;
        $this->groupingActivities = $groupingActivities;
        $this->categoryActivities = $categoryActivities;
        $this->otherActivities = $otherActivities;
    }

    public function withAddedParentActivity(Activity $parentActivity): self
    {
        $contextActivities = clone $this;

        if (!is_array($contextActivities->parentActivities)) {
            $contextActivities->parentActivities = array();
        }

        $contextActivities->parentActivities[] = $parentActivity;

        return $contextActivities;
    }

    public function withoutParentActivities(): self
    {
        $contextActivities = clone $this;
        $contextActivities->parentActivities = null;

        return $contextActivities;
    }

    public function withAddedGroupingActivity(Activity $groupingActivity): self
    {
        $contextActivities = clone $this;

        if (!is_array($contextActivities->groupingActivities)) {
            $contextActivities->groupingActivities = array();
        }

        $contextActivities->groupingActivities[] = $groupingActivity;

        return $contextActivities;
    }

    public function withoutGroupingActivities(): self
    {
        $contextActivities = clone $this;
        $contextActivities->groupingActivities = null;

        return $contextActivities;
    }

    public function withAddedCategoryActivity(Activity $categoryActivity): self
    {
        $contextActivities = clone $this;

        if (!is_array($contextActivities->categoryActivities)) {
            $contextActivities->categoryActivities = array();
        }

        $contextActivities->categoryActivities[] = $categoryActivity;

        return $contextActivities;
    }

    public function withoutCategoryActivities(): self
    {
        $contextActivities = clone $this;
        $contextActivities->categoryActivities = null;

        return $contextActivities;
    }

    public function withAddedOtherActivity(Activity $otherActivity): self
    {
        $contextActivities = clone $this;

        if (!is_array($contextActivities->otherActivities)) {
            $contextActivities->otherActivities = array();
        }

        $contextActivities->otherActivities[] = $otherActivity;

        return $contextActivities;
    }

    public function withoutOtherActivities(): self
    {
        $contextActivities = clone $this;
        $contextActivities->otherActivities = null;

        return $contextActivities;
    }

    /**
     * @return Activity[]|null
     */
    public function getParentActivities(): ?array
    {
        return $this->parentActivities;
    }

    /**
     * @return Activity[]|null
     */
    public function getGroupingActivities(): ?array
    {
        return $this->groupingActivities;
    }

    /**
     * @return Activity[]|null
     */
    public function getCategoryActivities(): ?array
    {
        return $this->categoryActivities;
    }

    /**
     * @return Activity[]|null
     */
    public function getOtherActivities(): ?array
    {
        return $this->otherActivities;
    }
}
