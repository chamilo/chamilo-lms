<?php

/**
 * This file is part of the HttpAdapter library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HttpAdapter;

/**
 * HttpAdapter interface.
 *
 * @author William Durand <william.durand1@gmail.com>
 * @author Antoine Corcy <contact@sbin.dk>
 */
interface HttpAdapterInterface
{
    /**
     * Returns the content fetched from a given URL.
     *
     * @param string $url
     *
     * @return string
     */
    public function getContent($url);

    /**
     * Returns the name of the HTTP Adapter.
     *
     * @return string
     */
    public function getName();
}
