<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Message;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class InboundMailboxDsn
{
    private function __construct(
        private string $scheme,
        private string $host,
        private int $port,
        private string $username,
        private string $password,
        private string $mailbox,
    ) {}

    public static function fromString(string $dsn): self
    {
        $dsn = trim($dsn);
        if ('' === $dsn) {
            throw new InvalidArgumentException('Inbound mailbox DSN is empty.');
        }

        $parts = parse_url($dsn);
        if (false === $parts) {
            throw new InvalidArgumentException('Inbound mailbox DSN is invalid.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!\in_array($scheme, ['imap', 'imaps'], true)) {
            throw new InvalidArgumentException('Inbound mailbox DSN must use imap:// or imaps://.');
        }

        $host = trim((string) ($parts['host'] ?? ''));
        if ('' === $host) {
            throw new InvalidArgumentException('Inbound mailbox DSN requires a host.');
        }

        $username = rawurldecode((string) ($parts['user'] ?? ''));
        if ('' === $username) {
            throw new InvalidArgumentException('Inbound mailbox DSN requires a username.');
        }

        $password = rawurldecode((string) ($parts['pass'] ?? ''));
        $port = (int) ($parts['port'] ?? ('imaps' === $scheme ? 993 : 143));
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Inbound mailbox DSN contains an invalid port.');
        }

        $mailbox = rawurldecode(ltrim((string) ($parts['path'] ?? ''), '/'));
        if ('' === $mailbox) {
            $mailbox = 'INBOX';
        }

        if (isset($parts['query']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('Inbound mailbox DSN must not contain a query string or fragment.');
        }

        return new self($scheme, $host, $port, $username, $password, $mailbox);
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getMailboxUrl(): string
    {
        $host = str_contains($this->host, ':') ? '['.$this->host.']' : $this->host;
        $mailbox = implode('/', array_map('rawurlencode', explode('/', $this->mailbox)));

        return \sprintf('%s://%s:%d/%s', $this->scheme, $host, $this->port, $mailbox);
    }

    public function getSafeDescription(): string
    {
        $host = str_contains($this->host, ':') ? '['.$this->host.']' : $this->host;

        return \sprintf('%s://%s:%d/%s', $this->scheme, $host, $this->port, $this->mailbox);
    }
}
