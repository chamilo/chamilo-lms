<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

@trigger_error(
    'The '.__NAMESPACE__.'\JsonType class is deprecated since 1.2 in favor of '.
    'Doctrine\DBAL\Types\JsonType, and will be removed in 2.0.',
    E_USER_DEPRECATED
);

/**
 * Convert a value into a json string to be stored into the persistency layer.
 */
class JsonType extends Type
{
    public const JSON = 'json';

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return json_decode((string) $value, true);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return json_encode($value);
    }

    public function getName()
    {
        return self::JSON;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
