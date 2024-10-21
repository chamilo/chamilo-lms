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
use Onlyoffice\DocsIntegrationSdk\Models\Anonymous;
use Onlyoffice\DocsIntegrationSdk\Models\Customer;
use Onlyoffice\DocsIntegrationSdk\Models\Features;
use Onlyoffice\DocsIntegrationSdk\Models\Logo;
use Onlyoffice\DocsIntegrationSdk\Models\Review;
use Onlyoffice\DocsIntegrationSdk\Models\Unit;

class Customization extends JsonSerializable
{
    protected $anonymous;
    protected $autosave;
    protected $comments;
    protected $compactHeader;
    protected $compactToolbar;
    protected $compatibleFeatures;
    protected $customer;
    protected $features;
    protected $feedback;
    protected $forcesave;
    protected $goback;
    protected $help;
    protected $hideNotes;
    protected $hideRightMenu;
    protected $hideRulers;
    protected $integrationMode;
    protected $logo;
    protected $macros;
    protected $macrosMode;
    protected $mentionShare;
    protected $mobileForceView;
    protected $plugins;
    protected $review;
    protected $submitForm;
    protected $toolbarHideFileName;
    protected $toolbarNoTabs;
    protected $uiTheme;
    protected $unit;
    protected $zoom;

    public function __construct(
        ?Anonymous $anonymous = null,
        ?bool $autosave = null,
        ?bool $comments = null,
        ?bool $compactHeader = null,
        ?bool $compactToolbar = null,
        ?bool $compatibleFeatures = null,
        ?Customer $customer = null,
        ?Features $features = null,
        ?bool $feedback = null,
        ?bool $forcesave = null,
        ?GoBack $goback = null,
        ?bool $help = true,
        ?bool $hideNotes = null,
        ?bool $hideRightMenu = null,
        ?bool $hideRulers = null,
        ?string $integrationMode = "embed",
        ?Logo $logo = null,
        ?bool $macros = null,
        ?MacrosMode $macrosMode = null,
        ?bool $mentionShare = null,
        ?bool $mobileForceView = null,
        ?bool $plugins = null,
        ?Review $review = null,
        ?bool $submitForm = null,
        ?bool $toolbarHideFileName = null,
        ?bool $toolbarNoTabs = null,
        ?string $uiTheme = "",
        ?Unit $unit = null,
        ?int $zoom = 100
    ) {
        $this->anonymous =  $anonymous;
        $this->autosave = $autosave;
        $this->comments = $comments;
        $this->compactHeader = $compactHeader;
        $this->compactToolbar = $compactToolbar;
        $this->compatibleFeatures = $compatibleFeatures;
        $this->customer = $customer;
        $this->features = $features;
        $this->feedback = $feedback;
        $this->forcesave = $forcesave;
        $this->goback = $goback;
        $this->help = $help;
        $this->hideNotes = $hideNotes;
        $this->hideRightMenu = $hideRightMenu;
        $this->hideRulers = $hideRulers;
        $this->integrationMode = $integrationMode;
        $this->logo = $logo;
        $this->macros = $macros;
        $this->macrosMode = $macrosMode;
        $this->mentionShare = $mentionShare;
        $this->mobileForceView = $mobileForceView;
        $this->plugins = $plugins;
        $this->review = $review;
        $this->submitForm = $submitForm;
        $this->toolbarHideFileName = $toolbarHideFileName;
        $this->toolbarNoTabs = $toolbarNoTabs;
        $this->uiTheme = $uiTheme;
        $this->unit = $unit;
        $this->zoom = $zoom;
    }

    /**
     * Get the value of anonymous
     */
    public function getAnonymous()
    {
        return $this->anonymous;
    }

    /**
     * Set the value of anonymous
     */
    public function setAnonymous($anonymous)
    {
        $this->anonymous = $anonymous;
    }

    /**
     * Get the value of autosave
     */
    public function getAutosave()
    {
        return $this->autosave;
    }

    /**
     * Set the value of autosave
     */
    public function setAutosave($autosave)
    {
        $this->autosave = $autosave;
    }

    /**
     * Get the value of comments
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set the value of comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * Get the value of compactHeader
     */
    public function getCompactHeader()
    {
        return $this->compactHeader;
    }

    /**
     * Set the value of compactHeader
     */
    public function setCompactHeader($compactHeader)
    {
        $this->compactHeader = $compactHeader;
    }

    /**
     * Get the value of compactToolbar
     */
    public function getCompactToolbar()
    {
        return $this->compactToolbar;
    }

    /**
     * Set the value of compactToolbar
     */
    public function setCompactToolbar($compactToolbar)
    {
        $this->compactToolbar = $compactToolbar;
    }

    /**
     * Get the value of compatibleFeatures
     */
    public function getCompatibleFeatures()
    {
        return $this->compatibleFeatures;
    }

    /**
     * Set the value of compatibleFeatures
     */
    public function setCompatibleFeatures($compatibleFeatures)
    {
        $this->compatibleFeatures = $compatibleFeatures;
    }

    /**
     * Get the value of customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Set the value of customer
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
    }

    /**
     * Get the value of features
     */
    public function getFeatures()
    {
        return $this->features;
    }

    /**
     * Set the value of features
     */
    public function setFeatures($features)
    {
        $this->features = $features;
    }

    /**
     * Get the value of feedback
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * Set the value of feedback
     */
    public function setFeedback($feedback)
    {
        $this->feedback = $feedback;
    }

    /**
     * Get the value of forcesave
     */
    public function getForcesave()
    {
        return $this->forcesave;
    }

    /**
     * Set the value of forcesave
     */
    public function setForcesave($forcesave)
    {
        $this->forcesave = $forcesave;
    }

    /**
     * Get the value of goback
     */
    public function getGoback()
    {
        return $this->goback;
    }

    /**
     * Set the value of goback
     */
    public function setGoback($goback)
    {
        $this->goback = $goback;
    }

    /**
     * Get the value of help
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * Set the value of help
     */
    public function setHelp($help)
    {
        $this->help = $help;
    }

    /**
     * Get the value of hideNotes
     */
    public function getHideNotes()
    {
        return $this->hideNotes;
    }

    /**
     * Set the value of hideNotes
     */
    public function setHideNotes($hideNotes)
    {
        $this->hideNotes = $hideNotes;
    }

    /**
     * Get the value of hideRightMenu
     */
    public function getHideRightMenu()
    {
        return $this->hideRightMenu;
    }

    /**
     * Set the value of hideRightMenu
     */
    public function setHideRightMenu($hideRightMenu)
    {
        $this->hideRightMenu = $hideRightMenu;
    }

    /**
     * Get the value of hideRulers
     */
    public function getHideRulers()
    {
        return $this->hideRulers;
    }

    /**
     * Set the value of hideRulers
     */
    public function setHideRulers($hideRulers)
    {
        $this->hideRulers = $hideRulers;
    }

    /**
     * Get the value of integrationMode
     */
    public function getIntegrationMode()
    {
        return $this->integrationMode;
    }

    /**
     * Set the value of integrationMode
     */
    public function setIntegrationMode($integrationMode)
    {
        $this->integrationMode = $integrationMode;
    }

    /**
     * Get the value of logo
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set the value of logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    /**
     * Get the value of macros
     */
    public function getMacros()
    {
        return $this->macros;
    }

    /**
     * Set the value of macros
     */
    public function setMacros($macros)
    {
        $this->macros = $macros;
    }

    /**
     * Get the value of macrosMode
     */
    public function getMacrosMode()
    {
        return $this->macrosMode;
    }

    /**
     * Set the value of macrosMode
     */
    public function setMacrosMode($macrosMode)
    {
        $this->macrosMode = $macrosMode;
    }

    /**
     * Get the value of mentionShare
     */
    public function getMentionShare()
    {
        return $this->mentionShare;
    }

    /**
     * Set the value of mentionShare
     */
    public function setMentionShare($mentionShare)
    {
        $this->mentionShare = $mentionShare;
    }

    /**
     * Get the value of mobileForceView
     */
    public function getMobileForceView()
    {
        return $this->mobileForceView;
    }

    /**
     * Set the value of mobileForceView
     */
    public function setMobileForceView($mobileForceView)
    {
        $this->mobileForceView = $mobileForceView;
    }

    /**
     * Get the value of plugins
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Set the value of plugins
     */
    public function setPlugins($plugins)
    {
        $this->plugins = $plugins;
    }

    /**
     * Get the value of review
     */
    public function getReview()
    {
        return $this->review;
    }

    /**
     * Set the value of review
     */
    public function setReview($review)
    {
        $this->review = $review;
    }

    /**
     * Get the value of submitForm
     */
    public function getSubmitForm()
    {
        return $this->submitForm;
    }

    /**
     * Set the value of submitForm
     */
    public function setSubmitForm($submitForm)
    {
        $this->submitForm = $submitForm;
    }

    /**
     * Get the value of toolbarHideFileName
     */
    public function getToolbarHideFileName()
    {
        return $this->toolbarHideFileName;
    }

    /**
     * Set the value of toolbarHideFileName
     */
    public function setToolbarHideFileName($toolbarHideFileName)
    {
        $this->toolbarHideFileName = $toolbarHideFileName;
    }

    /**
     * Get the value of toolbarNoTabs
     */
    public function getToolbarNoTabs()
    {
        return $this->toolbarNoTabs;
    }

    /**
     * Set the value of toolbarNoTabs
     */
    public function setToolbarNoTabs($toolbarNoTabs)
    {
        $this->toolbarNoTabs = $toolbarNoTabs;
    }

    /**
     * Get the value of uiTheme
     */
    public function getUiTheme()
    {
        return $this->uiTheme;
    }

    /**
     * Set the value of uiTheme
     */
    public function setUiTheme($uiTheme)
    {
        $this->uiTheme = $uiTheme;
    }

    /**
     * Get the value of unit
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set the value of unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * Get the value of zoom
     */
    public function getZoom()
    {
        return $this->zoom;
    }

    /**
     * Set the value of zoom
     */
    public function setZoom($zoom)
    {
        $this->zoom = $zoom;
    }
}
