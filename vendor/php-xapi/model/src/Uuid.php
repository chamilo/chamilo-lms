<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

use Ramsey\Uuid\Uuid as RamseyUuid;
use Rhumsaa\Uuid\Uuid as RhumsaaUuid;

/**
 * @author Jérôme Parmentier <jerome.parmentier@acensi.fr>
 */
final class Uuid
{
    /**
     * @var RamseyUuid|RhumsaaUuid;
     */
    private $uuid;

    private function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    public static function fromString(string $uuid): self
    {
        if (class_exists(RhumsaaUuid::class)) {
            return new self(RhumsaaUuid::fromString($uuid));
        }

        return new self(RamseyUuid::fromString($uuid));
    }

    /**
     * Generate a version 1 UUID from a host ID, sequence number, and the current time.
     *
     * @param int|string $node     a 48-bit number representing the hardware address
     *                             This number may be represented as an integer or a hexadecimal string
     * @param int        $clockSeq a 14-bit number used to help avoid duplicates that
     *                             could arise when the clock is set backwards in time or if the node ID
     *                             changes
     */
    public static function uuid1($node = null, int $clockSeq = null): self
    {
        if (class_exists(RhumsaaUuid::class)) {
            return new self(RhumsaaUuid::uuid1($node, $clockSeq));
        }

        return new self(RamseyUuid::uuid1($node, $clockSeq));
    }

    /**
     * Generate a version 3 UUID based on the MD5 hash of a namespace identifier
     * (which is a UUID) and a name (which is a string).
     *
     * @param string $ns   The UUID namespace in which to create the named UUID
     * @param string $name The name to create a UUID for
     */
    public static function uuid3(string $ns, string $name): self
    {
        if (class_exists(RhumsaaUuid::class)) {
            return new self(RhumsaaUuid::uuid3($ns, $name));
        }

        return new self(RamseyUuid::uuid3($ns, $name));
    }

    /**
     * Generate a version 4 (random) UUID.
     */
    public static function uuid4(): self
    {
        if (class_exists(RhumsaaUuid::class)) {
            return new self(RhumsaaUuid::uuid4());
        }

        return new self(RamseyUuid::uuid4());
    }

    /**
     * Generate a version 5 UUID based on the SHA-1 hash of a namespace
     * identifier (which is a UUID) and a name (which is a string).
     *
     * @param string $ns   The UUID namespace in which to create the named UUID
     * @param string $name The name to create a UUID for
     */
    public static function uuid5(string $ns, string $name): self
    {
        if (class_exists(RhumsaaUuid::class)) {
            return new self(RhumsaaUuid::uuid5($ns, $name));
        }

        return new self(RamseyUuid::uuid5($ns, $name));
    }

    public function __toString(): string
    {
        return $this->uuid->toString();
    }

    public function equals(Uuid $uuid): bool
    {
        return $this->uuid->equals($uuid->uuid);
    }
}
