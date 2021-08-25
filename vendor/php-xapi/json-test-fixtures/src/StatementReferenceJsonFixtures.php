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
 * JSON encoded xAPI statement reference fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class StatementReferenceJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'StatementReference';

    public static function getTypicalStatementReference()
    {
        return self::load('typical');
    }

    public static function getAllPropertiesStatementReference()
    {
        return self::load('all_properties');
    }
}
