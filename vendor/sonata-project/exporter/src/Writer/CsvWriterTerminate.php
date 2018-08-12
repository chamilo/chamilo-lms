<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Writer;

/**
 * Filter CSV output to replace the default terminator while supporting active streams.
 */
final class CsvWriterTerminate extends \php_user_filter
{
    /**
     * @param $in
     * @param $out
     * @param $consumed
     * @param $closing
     *
     * @return int
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            if (isset($this->params['terminate'])) {
                $bucket->data = preg_replace('/([^\r])\n/', '$1'.$this->params['terminate'], $bucket->data);
            }
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}
