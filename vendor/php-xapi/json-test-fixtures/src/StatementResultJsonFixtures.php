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
 * JSON encoded statement result fixtures.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class StatementResultJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'StatementResult';

    /**
     * Loads a statement result.
     *
     * @return string
     */
    public static function getStatementResult()
    {
        return static::load('result');
    }

    /**
     * Loads a statement result including a more reference.
     *
     * @return string
     */
    public static function getStatementResultWithMore()
    {
        return static::load('result_with_more');
    }
}
