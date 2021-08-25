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
 * JSON encoded xAPI statement activity interaction component fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class InteractionComponentJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'InteractionComponent';

    public static function getTypicalInteractionComponent()
    {
        return self::load('typical');
    }

    public static function getIdOnlyInteractionComponent()
    {
        return self::load('id_only');
    }

    public static function getAllPropertiesInteractionComponent()
    {
        return self::load('all_properties');
    }
}
