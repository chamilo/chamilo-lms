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

declare(strict_types=1);

use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\DocumentActionEvent;
use Chamilo\CoreBundle\Event\DocumentItemActionEvent;
use Chamilo\CoreBundle\Event\DocumentItemViewEvent;
use Chamilo\CoreBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OnlyofficeEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::DOCUMENT_ACTION => 'onDocumentAction',
            Events::DOCUMENT_ITEM_ACTION => 'onDocumentItemAction',
            Events::DOCUMENT_ITEM_VIEW => 'onDocumentItemView',
        ];
    }

    /**
     * Create the Onlyoffice edit tools when the Chamilo loads document tools.
     */
    public function onDocumentAction(DocumentActionEvent $event): void
    {
        if (AbstractEvent::TYPE_PRE === $event->getType()) {
            $action = $event->getData()['action'];
            $action[] = OnlyofficeTools::getButtonCreateNew();

            $event->setData(['action' => $action]);
        }
    }

    /**
     * Create the Onlyoffice edit tools when the Chamilo loads document items.
     */
    public function onDocumentItemAction(DocumentItemActionEvent $event): void
    {
        if (AbstractEvent::TYPE_PRE === $event->getType()) {
            $action = $event->getAction();
            $action[] = OnlyofficeTools::getButtonEdit($event->getDocument());

            $event->setData(['action' => $action]);
        }
    }

    /**
     * Create the Onlyoffice view tools when the Chamilo loads document items.
     */
    public function onDocumentItemView(DocumentItemViewEvent $event): void
    {
        $document = $event->getDocument();

        $link = OnlyofficeTools::getButtonView(
            [
                'iid' => $document->getIid(),
                'title' => $document->getTitle(),
            ]
        );

        $event->addLink($link);
    }
}