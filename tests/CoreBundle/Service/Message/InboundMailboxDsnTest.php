<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Message;

use Chamilo\CoreBundle\Service\Message\InboundMailboxDsn;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InboundMailboxDsnTest extends TestCase
{
    public function testParsesSecureDsnAndDecodesCredentials(): void
    {
        $dsn = InboundMailboxDsn::fromString(
            'imaps://user%40example.com:p%40ss%3Aword@imap.example.com:993/Support%20Replies'
        );

        $this->assertSame('imaps', $dsn->getScheme());
        $this->assertSame('user@example.com', $dsn->getUsername());
        $this->assertSame('p@ss:word', $dsn->getPassword());
        $this->assertSame('imaps://imap.example.com:993/Support%20Replies', $dsn->getMailboxUrl());
        $this->assertSame('imaps://imap.example.com:993/Support Replies', $dsn->getSafeDescription());
    }

    public function testUsesImapDefaults(): void
    {
        $dsn = InboundMailboxDsn::fromString('imap://user:secret@mail.example.com');

        $this->assertSame('imap://mail.example.com:143/INBOX', $dsn->getMailboxUrl());
    }

    public function testRejectsUnsupportedProtocol(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('imap:// or imaps://');

        InboundMailboxDsn::fromString('https://user:secret@example.com/INBOX');
    }

    public function testRejectsDsnWithoutUsername(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a username');

        InboundMailboxDsn::fromString('imaps://imap.example.com/INBOX');
    }
}
