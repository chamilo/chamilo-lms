<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\Types;
use JsonException;

use const JSON_THROW_ON_ERROR;

/**
 * Drop-in replacement for the built-in "json" type that also reads legacy
 * PHP-serialized values.
 *
 * The deprecated Doctrine "array" type stored data via serialize(). Nine columns
 * were switched to "json" during the DBAL 3/4 upgrade, but an existing install
 * still holds serialized data until the V300 data migration converts it. Any ORM
 * read of those columns before that migration would otherwise fail on
 * json_decode(). This type falls back to unserialize() on that failure, so an
 * upgrade never breaks regardless of how old the install is. Writes are always
 * JSON (inherited from JsonType). Once every row is JSON the fallback is inert.
 */
final class TolerantJsonType extends JsonType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (\is_resource($value)) {
            $value = stream_get_contents($value);
        }

        $value = (string) $value;

        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            // Legacy path: value was written by the old "array" type (serialize()).
            // allowed_classes: false rejects object injection — these columns only
            // ever hold arrays/scalars.
            $unserialized = @unserialize($value, ['allowed_classes' => false]);
            if (false !== $unserialized) {
                return $unserialized;
            }

            // Neither JSON nor serialized: genuinely corrupt, surface it.
            throw ConversionException::conversionFailed($value, Types::JSON, $jsonException);
        }
    }
}
