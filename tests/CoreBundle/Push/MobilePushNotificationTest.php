<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Push;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Push\MobilePushNotification;
use PHPUnit\Framework\TestCase;

final class MobilePushNotificationTest extends TestCase
{
    public function testBuildsTitleSenderAndPlainTextPreviewFromMessage(): void
    {
        $sender = $this->createMock(User::class);
        $sender->method('getFullName')->willReturn('Ada Lovelace');
        $sender->method('getUsername')->willReturn('ada');

        $message = $this->createMock(Message::class);
        $message->method('getId')->willReturn(42);
        $message->method('getTitle')->willReturn('Project &amp; review');
        $message->method('getContent')->willReturn('<p>Hello <strong>team</strong>.</p><p>Second line.</p>');
        $message->method('getSender')->willReturn($sender);

        $notification = MobilePushNotification::fromMessage($message);

        self::assertNotNull($notification);
        self::assertSame(42, $notification->messageId);
        self::assertSame('Project & review', $notification->title);
        self::assertSame('Ada Lovelace · Hello team. Second line.', $notification->body);
    }

    public function testUsesSafeFallbacksAndTruncatesLongContent(): void
    {
        $sender = $this->createMock(User::class);
        $sender->method('getFullName')->willReturn('');
        $sender->method('getUsername')->willReturn('teacher');

        $message = $this->createMock(Message::class);
        $message->method('getId')->willReturn(7);
        $message->method('getTitle')->willReturn('   ');
        $message->method('getContent')->willReturn('<div>'.str_repeat('Long message ', 40).'</div>');
        $message->method('getSender')->willReturn($sender);

        $notification = MobilePushNotification::fromMessage($message);

        self::assertNotNull($notification);
        self::assertSame('Chamilo', $notification->title);
        self::assertStringStartsWith('teacher · Long message', $notification->body);
        self::assertLessThanOrEqual(183, mb_strwidth($notification->body));
    }

    public function testReturnsNullForAnUnpersistedMessage(): void
    {
        $message = $this->createMock(Message::class);
        $message->method('getId')->willReturn(null);

        self::assertNull(MobilePushNotification::fromMessage($message));
    }
}
