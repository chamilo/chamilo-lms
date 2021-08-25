<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\DataFixtures;

use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementReference;

/**
 * xAPI statement reference fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class StatementReferenceFixtures
{
    public static function getTypicalStatementReference()
    {
        return new StatementReference(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da'));
    }

    public static function getAllPropertiesStatementReference()
    {
        return new StatementReference(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da'));
    }
}
