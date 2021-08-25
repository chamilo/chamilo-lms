<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Fixtures\Json;

/**
 * xAPI statement activity fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class ActivityJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'Activity';

    public static function getTypicalActivity()
    {
        return self::load('typical');
    }

    public static function getIdActivity()
    {
        return self::load('id');
    }

    public static function getIdActivityWithType()
    {
        return self::load('id_with_type');
    }

    public static function getIdAndDefinitionActivity()
    {
        return self::load('id_and_definition');
    }

    public static function getIdAndDefinitionActivityWithType()
    {
        return self::load('id_and_definition_with_type');
    }

    public static function getAllPropertiesActivity()
    {
        return self::load('all_properties');
    }

    public static function getForQueryActivity()
    {
        return self::load('for_query');
    }
}
