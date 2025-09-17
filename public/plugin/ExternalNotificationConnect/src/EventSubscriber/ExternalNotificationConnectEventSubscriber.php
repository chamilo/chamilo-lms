<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Event\AbstractEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\LearningPathCreatedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemAddedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemDeletedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemEditedEvent;
use Chamilo\CoreBundle\Event\PortfolioItemVisibilityChangedEvent;
use Chamilo\PluginBundle\ExternalNotificationConnect\Traits\RequestTrait\RequestTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExternalNotificationConnectEventSubscriber implements EventSubscriberInterface
{
    use RequestTrait;

    private ExternalNotificationConnectPlugin $plugin;

    public function __construct()
    {
        $this->plugin = ExternalNotificationConnectPlugin::create();
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PORTFOLIO_ITEM_ADDED => 'onPortfolioItemAdded',
            Events::PORTFOLIO_ITEM_EDITED => 'onPortfolioItemEdited',
            Events::PORTFOLIO_ITEM_DELETED => 'onPortfolioItemDeleted',
            Events::PORTFOLIO_ITEM_VISIBILITY_CHANGED => 'onPortfolioItemVisibility',

            Events::LP_CREATED => 'onLpCreated',
        ];
    }

    public function onPortfolioItemAdded(PortfolioItemAddedEvent $event): void
    {
        if (!$this->plugin->isEnabled(true)
            || 'true' !== $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFY_PORTFOLIO)
        ) {
            return;
        }

        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        $url = '/main/portfolio/index.php?'.http_build_query(['action' => 'view', 'id' => $item->getId()]);

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

    public function onPortfolioItemEdited(PortfolioItemEditedEvent $event): void
    {
        if (!$this->plugin->isEnabled(true)
            || 'true' !== $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFY_PORTFOLIO)
        ) {
            return;
        }

        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        $url = '/main/portfolio/index.php?'.http_build_query(['action' => 'view', 'id' => $item->getId()]);

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

    public function onPortfolioItemDeleted(PortfolioItemDeletedEvent $event): void
    {
        if (!$this->plugin->isEnabled(true)
            || 'true' !== $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFY_PORTFOLIO)
        ) {
            return;
        }

        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        if (AbstractEvent::TYPE_PRE === $event->getType()) {
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

    public function onPortfolioItemVisibility(PortfolioItemVisibilityChangedEvent $event): void
    {
        if (!$this->plugin->isEnabled(true)
            || 'true' !== $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFY_PORTFOLIO)
        ) {
            return;
        }

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

    public function onLpCreated(LearningPathCreatedEvent $event): void
    {
        if (!$this->plugin->isEnabled(true)
            || 'true' !== $this->plugin->get(ExternalNotificationConnectPlugin::SETTING_NOTIFY_LEARNPATH)
        ) {
            return;
        }

        $lp = $event->getLp();

        if (!$lp) {
            return;
        }

        $url = '/main/lp/lp_controller.php?'
            .http_build_query(['action' => 'view', 'lp_id' => $lp->getIid(), 'isStudentView' => 'true']);

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
