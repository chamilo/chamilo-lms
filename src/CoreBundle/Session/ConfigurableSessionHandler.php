<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Session;

use RuntimeException;
use SensitiveParameter;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

final readonly class ConfigurableSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    public function __construct(
        private SessionHandlerInterface $nativeHandler,
        private SessionHandlerInterface $databaseHandler,
        private bool $storeInDatabase,
    ) {}

    public function open(string $path, string $name): bool
    {
        return $this->getHandler()->open($path, $name);
    }

    public function close(): bool
    {
        return $this->getHandler()->close();
    }

    public function read(#[SensitiveParameter] string $id): false|string
    {
        return $this->getHandler()->read($id);
    }

    public function write(#[SensitiveParameter] string $id, string $data): bool
    {
        return $this->getHandler()->write($id, $data);
    }

    public function destroy(#[SensitiveParameter] string $id): bool
    {
        return $this->getHandler()->destroy($id);
    }

    public function gc(int $maxLifetime): false|int
    {
        return $this->getHandler()->gc($maxLifetime);
    }

    public function validateId(#[SensitiveParameter] string $id): bool
    {
        $handler = $this->getHandler();

        if ($handler instanceof SessionUpdateTimestampHandlerInterface) {
            return $handler->validateId($id);
        }

        $data = $handler->read($id);

        return false !== $data && '' !== $data;
    }

    public function create_sid(): string
    {
        $handler = $this->getHandler();

        if (method_exists($handler, 'create_sid')) {
            return $handler->create_sid();
        }

        return session_create_id() ?: throw new RuntimeException('Unable to create a session ID.');
    }

    public function updateTimestamp(#[SensitiveParameter] string $id, string $data): bool
    {
        $handler = $this->getHandler();

        if ($handler instanceof SessionUpdateTimestampHandlerInterface) {
            return $handler->updateTimestamp($id, $data);
        }

        return $handler->write($id, $data);
    }

    private function getHandler(): SessionHandlerInterface
    {
        return $this->storeInDatabase ? $this->databaseHandler : $this->nativeHandler;
    }
}
