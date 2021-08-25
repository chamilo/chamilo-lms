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
 * JSON encoded document fixtures.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class DocumentJsonFixtures extends JsonFixtures
{
    /**
     * Loads a document.
     *
     * @return string
     */
    public static function getDocument()
    {
        return static::load('document');
    }
}
