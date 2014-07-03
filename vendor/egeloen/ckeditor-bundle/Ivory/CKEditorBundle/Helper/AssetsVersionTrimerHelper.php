<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\Helper;

/**
 * Assets version trimer helper.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class AssetsVersionTrimerHelper
{
    /**
     * Trims the version of an asset.
     *
     * @param string $asset The asset.
     *
     * @return string The asset with the version trimmed.
     */
    public function trim($asset)
    {
        if (($position = strpos($asset, '?')) !== false) {
            return substr($asset, 0, $position);
        }

        return $asset;
    }
}
