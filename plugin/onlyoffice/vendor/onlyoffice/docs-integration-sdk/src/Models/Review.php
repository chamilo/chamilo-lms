<?php

namespace Onlyoffice\DocsIntegrationSdk\Models;

/**
 *
 * (c) Copyright Ascensio System SIA 2024
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
use Onlyoffice\DocsIntegrationSdk\Models\ReviewDisplay;

class Review extends JsonSerializable
{
    protected $hideReviewDisplay;
    protected $hoverMode;
    protected $reviewDisplay;
    protected $showReviewChanges;
    protected $trackChanges;

    public function __construct(
        bool $hideReviewDisplay = false,
        bool $hoverMode = false,
        ReviewDisplay $reviewDisplay = null,
        bool $showReviewChanges = false,
        bool $trackChanges = true
    ) {
        $this->hideReviewDisplay = $hideReviewDisplay;
        $this->hoverMode = $hoverMode;
        $this->reviewDisplay = $reviewDisplay !== null ? $reviewDisplay : new ReviewDisplay;
        $this->showReviewChanges = $showReviewChanges;
        $this->trackChanges = $trackChanges;
    }

    /**
     * Get the value of hideReviewDisplay
     */
    public function getHideReviewDisplay()
    {
        return $this->hideReviewDisplay;
    }

    /**
     * Set the value of hideReviewDisplay
     */
    public function setHideReviewDisplay($hideReviewDisplay)
    {
        $this->hideReviewDisplay = $hideReviewDisplay;
    }

    /**
     * Get the value of hoverMode
     */
    public function getHoverMode()
    {
        return $this->hoverMode;
    }

    /**
     * Set the value of hoverMode
     */
    public function setHoverMode($hoverMode)
    {
        $this->hoverMode = $hoverMode;
    }

    /**
     * Get the value of reviewDisplay
     */
    public function getReviewDisplay()
    {
        return $this->reviewDisplay;
    }

    /**
     * Set the value of reviewDisplay
     */
    public function setReviewDisplay($reviewDisplay)
    {
        $this->reviewDisplay = $reviewDisplay;
    }

    /**
     * Get the value of showReviewChanges
     */
    public function getShowReviewChanges()
    {
        return $this->showReviewChanges;
    }

    /**
     * Set the value of showReviewChanges
     */
    public function setShowReviewChanges($showReviewChanges)
    {
        $this->showReviewChanges = $showReviewChanges;
    }

    /**
     * Get the value of trackChanges
     */
    public function getTrackChanges()
    {
        return $this->trackChanges;
    }

    /**
     * Set the value of trackChanges
     */
    public function setTrackChanges($trackChanges)
    {
        $this->trackChanges = $trackChanges;
    }
}
