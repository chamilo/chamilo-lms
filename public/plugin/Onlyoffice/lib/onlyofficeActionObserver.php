<?php
/**
 * (c) Copyright Ascensio System SIA 2025.
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
 */
class OnlyofficeActionObserver extends HookObserver implements HookDocumentActionObserverInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            'plugin/onlyoffice/lib/onlyofficePlugin.php',
            'onlyoffice'
        );
    }

    /**
     * Create a Onlyoffice edit tools when the Chamilo loads document tools.
     *
     * @param HookDocumentActionEventInterface $event - the hook event
     */
    public function notifyDocumentAction(HookDocumentActionEventInterface $event)
    {
        $data = $event->getEventData();

        if (HOOK_EVENT_TYPE_PRE === $data['type']) {
            $data['actions'][] = OnlyofficeTools::getButtonCreateNew();

            return $data;
        }
    }
}
