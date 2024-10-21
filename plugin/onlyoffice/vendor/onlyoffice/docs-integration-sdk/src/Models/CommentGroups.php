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

class CommentGroups extends JsonSerializable
{
    protected $edit;
    protected $remove;
    protected $view;

    public function __construct(array $edit = [], array $remove = [], array $view = [])
    {
        $this->edit = $edit;
        $this->remove = $remove;
        $this->view = $view;
    }

    /**
     * Get the value of edit
     */
    public function getEdit()
    {
        return $this->edit;
    }

    /**
     * Set the value of edit
     */
    public function setEdit($edit)
    {
        $this->edit = $edit;
    }

    /**
     * Get the value of remove
     */
    public function getRemove()
    {
        return $this->remove;
    }

    /**
     * Set the value of remove
     */
    public function setRemove($remove)
    {
        $this->remove = $remove;
    }

    /**
     * Get the value of view
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Set the value of view
     */
    public function setView($view)
    {
        $this->view = $view;
    }
}
