<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\CourseAccessActivityHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CourseAccessActivityHelperTest extends TestCase
{
    /**
     * The docked global chat sends its polling with the course of the page: counted as
     * activity, a chat left open kept the learner "in the course" for hours.
     */
    public function testChatPollingIsNotCourseActivity(): void
    {
        $helper = new CourseAccessActivityHelper($this->createMock(SettingsManager::class));

        foreach ([
            'chamilo_core_chat_api_heartbeat',
            'chamilo_core_chat_api_history_since',
            'chamilo_core_chat_api_video_signals',
        ] as $route) {
            $this->assertTrue($helper->isBackgroundRequest($this->requestForRoute($route)), $route);
        }
    }

    /**
     * Sending a message is something the learner does on the course page: it still counts.
     */
    public function testChatActionsStillCountAsCourseActivity(): void
    {
        $helper = new CourseAccessActivityHelper($this->createMock(SettingsManager::class));

        $this->assertFalse($helper->isBackgroundRequest($this->requestForRoute('chamilo_core_chat_api_send')));
        $this->assertFalse($helper->isBackgroundRequest($this->requestForRoute('chamilo_core_chat_api_ack')));
    }

    private function requestForRoute(string $route): Request
    {
        $request = Request::create('/account/chat/api/x?cid=1&sid=1');
        $request->attributes->set('_route', $route);

        return $request;
    }
}
