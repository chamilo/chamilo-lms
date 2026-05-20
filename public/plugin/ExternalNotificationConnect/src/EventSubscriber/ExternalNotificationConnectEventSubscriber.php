<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

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
        if (!$this->plugin->isEnabled()
            || !$this->plugin->isPortfolioNotificationEnabled()
        ) {
            return;
        }

        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        try {
            $json = $this->doCreateRequest(
                [
                    'user_id' => api_get_user_entity()->getId(),
                    'course_code' => $this->getCurrentCourseCode(),
                    'content_id' => $item->getId(),
                    'content_type' => 'eportfolio',
                    'content_url' => $this->buildPortfolioUrl((int) $item->getId()),
                    'post_title' => $item->getTitle(),
                ]
            );
        } catch (Exception $e) {
            $this->logExternalNotificationError('Portfolio item creation notification failed.', $e);

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Portfolio item created: ID '.$this->getNotificationId($json));
    }

    public function onPortfolioItemEdited(PortfolioItemEditedEvent $event): void
    {
        if (!$this->plugin->isEnabled()
            || !$this->plugin->isPortfolioNotificationEnabled()
        ) {
            return;
        }

        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        try {
            $json = $this->doEditRequest(
                [
                    'user_id' => api_get_user_entity()->getId(),
                    'course_code' => $this->getCurrentCourseCode(),
                    'content_id' => $item->getId(),
                    'content_type' => 'eportfolio',
                    'content_url' => $this->buildPortfolioUrl((int) $item->getId()),
                    'post_title' => $item->getTitle(),
                ]
            );
        } catch (Exception $e) {
            $this->logExternalNotificationError('Portfolio item edition notification failed.', $e);

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Portfolio item edited. Status '.((int) ($json['status'] ?? 0)));
    }

    public function onPortfolioItemDeleted(PortfolioItemDeletedEvent $event): void
    {
        if (!$this->plugin->isEnabled()
            || !$this->plugin->isPortfolioNotificationEnabled()
        ) {
            return;
        }

        $item = $event->getPortfolio();

        if (!$item) {
            return;
        }

        if (AbstractEvent::TYPE_PRE !== $event->getType()) {
            return;
        }

        try {
            $json = $this->doDeleteRequest((int) $item->getId(), 'eportfolio');
        } catch (Exception $e) {
            $this->logExternalNotificationError('Portfolio item deletion notification failed.', $e);

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Portfolio item deleted: Status '.((int) ($json['status'] ?? 0)));
    }

    public function onPortfolioItemVisibility(PortfolioItemVisibilityChangedEvent $event): void
    {
        if (!$this->plugin->isEnabled()
            || !$this->plugin->isPortfolioNotificationEnabled()
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
            $this->logExternalNotificationError('Portfolio item visibility notification failed.', $e);

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Portfolio item visibility: ID '.$this->getNotificationId($json));
    }

    public function onLpCreated(LearningPathCreatedEvent $event): void
    {
        if (!$this->plugin->isEnabled()
            || !$this->plugin->isLearningPathNotificationEnabled()
        ) {
            return;
        }

        $lp = $event->getLp();

        if (!$lp) {
            return;
        }

        try {
            $json = $this->doCreateRequest(
                [
                    'user_id' => api_get_user_entity()->getId(),
                    'course_code' => $this->getCurrentCourseCode(),
                    'content_id' => $lp->getIid(),
                    'content_type' => 'lp',
                    'content_url' => $this->buildLearningPathUrl((int) $lp->getIid()),
                    'post_title' => $lp->getTitle(),
                ]
            );
        } catch (Exception $e) {
            $this->logExternalNotificationError('Learning path creation notification failed.', $e);

            return;
        }

        if (empty($json)) {
            return;
        }

        error_log('ExtNotifConn: Learning path created: ID '.$this->getNotificationId($json));
    }


    private function getCurrentCourseCode(): string
    {
        $course = api_get_course_entity();

        if (!$course) {
            return '';
        }

        return (string) $course->getCode();
    }

    private function buildPortfolioUrl(int $portfolioId): string
    {
        $url = api_get_path(WEB_CODE_PATH).'portfolio/index.php?'.http_build_query([
            'action' => 'view',
            'id' => $portfolioId,
        ]);

        return $this->appendCourseRequest($url);
    }

    private function buildLearningPathUrl(int $lpId): string
    {
        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.http_build_query([
            'action' => 'view',
            'lp_id' => $lpId,
            'isStudentView' => 'true',
        ]);

        return $this->appendCourseRequest($url);
    }

    private function appendCourseRequest(string $url): string
    {
        $cidReq = api_get_cidreq();

        if ('' === trim($cidReq)) {
            return $url;
        }

        return $url.'&'.$cidReq;
    }

    private function getNotificationId(array $json): string
    {
        return (string) ($json['data']['notification_id'] ?? 'unknown');
    }

    private function logExternalNotificationError(string $message, Exception $exception): void
    {
        error_log('ExtNotifConn: '.$message.' '.$exception->getMessage());
    }
}
