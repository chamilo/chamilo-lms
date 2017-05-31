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

use Guzzle\Service\Client;

/**
 * GuzzleHttpAdapter class.
 *
 * @author Michael Dowling <michael@guzzlephp.org>
 * @author Antoine Corcy <contact@sbin.dk>
 *
 * @link   http://www.guzzlephp.org
 */
class GuzzleHttpAdapter implements HttpAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getContent($url)
    {
        $guzzle = new Client();

        try {
            $content = (string) $guzzle->get($url)->send()->getBody();
        } catch (\Exception $e) {
            $content = null;
        }

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'guzzle';
    }
}
