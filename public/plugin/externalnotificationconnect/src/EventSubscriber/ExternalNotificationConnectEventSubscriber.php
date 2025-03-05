<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\HookEvent\HookEvent;
use Chamilo\CoreBundle\HookEvent\HookEvents;
use Chamilo\CoreBundle\HookEvent\LearningPathCreatedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemAddedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemDeletedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemEditedHookEvent;
use Chamilo\CoreBundle\HookEvent\PortfolioItemVisibilityChangedHookEvent;
use Chamilo\PluginBundle\ExternalNotificationConnect\Traits\RequestTrait\RequestTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExternalNotificationConnectEventSubscriber implements EventSubscriberInterface
{
    use RequestTrait;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            HookEvents::PORTFOLIO_ITEM_ADDED => 'onPortfolioItemAdded',
            HookEvents::PORTFOLIO_ITEM_EDITED => 'onPortfolioItemEdited',
            HookEvents::PORTOFLIO_ITEM_DELETED => 'onPortfolioItemDeleted',
            HookEvents::PORTFOLIO_ITEM_VISIBILITY_CHANGED => 'onPortfolioItemVisibility',

            HookEvents::LP_CREATED => 'onLpCreated',
        ];
    }

    public function onPortfolioItemAdded(PortfolioItemAddedHookEvent $event): void
    {
        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        $url = Container::getRouter()
            ->generate(
                'legacy_main',
                [
                    'name' => 'portfolio/index.php?',
                    'action' => 'view',
                    'id' => $item->getId(),
                ]
            );

        try {
            $json = $this->doCreateRequest(
                [
                    'user_id' => api_get_user_entity()->getId(),
                    'course_code' => api_get_course_entity()->getCode(),
                    'content_id' => $item->getId(),
                    'content_type' => 'eportfolio',
                    'content_url' => $url.'&'.api_get_cidreq(),
                    'post_title' => $item->getTitle(),
                ]
            );
        } catch (Exception $e) {
            Display::addFlash(
                Display::return_message($e->getMessage(), 'error')
            );

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Portfolio item created: ID '.$json['data']['notification_id']);
    }

    public function onPortfolioItemEdited(PortfolioItemEditedHookEvent $event): void
    {
        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        $url = Container::getRouter()
            ->generate(
                'legacy_main',
                [
                    'name' => 'portfolio/index.php?',
                    'action' => 'view',
                    'id' => $item->getId(),
                ]
            );

        try {
            $json = $this->doEditRequest(
                [
                    'user_id' => api_get_user_entity()->getId(),
                    'course_code' => api_get_course_entity()->getCode(),
                    'content_id' => $item->getId(),
                    'content_type' => 'eportfolio',
                    'content_url' => $url.'&'.api_get_cidreq(),
                    'post_title' => $item->getTitle(),
                ]
            );
        } catch (Exception $e) {
            Display::addFlash(
                Display::return_message($e->getMessage(), 'error')
            );

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Portfolio item edited. Status'.((int)$json['status']));
    }

    public function onPortfolioItemDeleted(PortfolioItemDeletedHookEvent $event): void
    {
        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        if (HookEvent::TYPE_PRE === $event->getType()) {
            try {
                $json = $this->doDeleteRequest($item->getId(), 'eportfolio');
            } catch (Exception $e) {
                Display::addFlash(
                    Display::return_message($e->getMessage(), 'error')
                );

                return;
            }

            if (empty($json)) {
                return;
            }

            error_log('ExtNotifConn: Portfolio item deleted: Status '.((int)$json['status']));
        }
    }

    public function onPortfolioItemVisibility(PortfolioItemVisibilityChangedHookEvent $event): void
    {
        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        $recipients = $event->getRecipientIdList();

        try {
            $json = $this->doVisibilityRequest(
                [
                    'content_id' => $item->getId(),
                    'content_type' => 'eportfolio',
                    'visibility' => (int) $item->isVisible(),
                    'user_list' => $recipients,
                ]
            );
        } catch (Exception $e) {
            Display::addFlash(
                Display::return_message($e->getMessage(), 'error')
            );

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Portfolio item visibility: ID '.$json['data']['notification_id']);
    }

    public function onLpCreated(LearningPathCreatedHookEvent $event): void
    {
        $lp = $event->getLp();

        if (!$lp) {
            return;
        }

        $url = Container::getRouter()
            ->generate(
                'legacy_main',
                [
                    'name' => 'lp/lp_controller.php',
                    'action' => 'view',
                    'lp_id' => $lp->getIid(),
                    'isStudentView' => 'true',
                ]
            );

        try {
            $json = $this->doCreateRequest(
                [
                    'user_id' => api_get_user_entity()->getId(),
                    'course_code' => api_get_course_entity()->getCode(),
                    'content_id' => $lp->getIid(),
                    'content_type' => 'lp',
                    'content_url' => $url.'&'.api_get_cidreq(),
                    'post_title' => $lp->getTitle(),
                ]
            );
        } catch (Exception $e) {
            Display::addFlash(
                Display::return_message($e->getMessage(), 'error')
            );

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Learning path created: ID '.$json['data']['notification_id']);
    }
}
