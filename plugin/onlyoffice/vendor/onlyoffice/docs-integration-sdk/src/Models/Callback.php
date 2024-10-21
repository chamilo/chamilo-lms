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
use Onlyoffice\DocsIntegrationSdk\Models\CallbackAction;
use Onlyoffice\DocsIntegrationSdk\Models\CallbackForceSaveType;
use Onlyoffice\DocsIntegrationSdk\Models\CallbackDocStatus;
use Onlyoffice\DocsIntegrationSdk\Models\History;

class Callback extends JsonSerializable
{
    protected $actions; //array of CallbackAction
    protected $changesurl;
    protected $fileType;
    protected $forceSaveType;
    protected $history;
    protected $key;
    protected $status;
    protected $url;
    protected $users;
    protected $token;

    public function __construct(
        array $actions = [],
        string $changesurl = "",
        string $fileType = "",
        CallbackForceSaveType $forceSaveType = null,
        History $history = null,
        string $key = "",
        CallbackDocStatus $status = null,
        string $url = "",
        array $users = [],
        string $token = ""
    ) {
        $this->actions = $actions;
        $this->changesurl = $changesurl;
        $this->fileType = $fileType;
        $this->forceSaveType = $forceSaveType !== null ? $forceSaveType : new CallbackForceSaveType;
        $this->history = $history !== null ? $history : new History;
        $this->key = $key;
        $this->status = $status !== null ? $status : new CallbackDocStatus;
        $this->url = $url;
        $this->users = $users;
        $this->token = $token;
    }

    /**
     * Get the value of actions
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Set the value of actions
     */
    public function setActions($actions)
    {
        $this->actions = $actions;
    }

    /**
     * Get the value of changesurl
     */
    public function getChangesurl()
    {
        return $this->changesurl;
    }

    /**
     * Set the value of changesurl
     */
    public function setChangesurl($changesurl)
    {
        $this->changesurl = $changesurl;
    }

    /**
     * Get the value of fileType
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * Set the value of fileType
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
    }

    /**
     * Get the value of forceSaveType
     */
    public function getForceSaveType()
    {
        return $this->forceSaveType;
    }

    /**
     * Set the value of forceSaveType
     */
    public function setForceSaveType($forceSaveType)
    {
        $this->forceSaveType = $forceSaveType;
    }

    /**
     * Get the value of history
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Set the value of history
     */
    public function setHistory($history)
    {
        $this->history = $history;
    }

    /**
     * Get the value of key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the value of key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get the value of status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the value of status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get the value of url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the value of url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get the value of users
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set the value of users
     */
    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * Get the value of token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set the value of token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
}
