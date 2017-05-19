<?php

/*
 * This file is part of PHP-SwfTools.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwfTools\Binary;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use SwfTools\Exception\RuntimeException;

/**
 * The abstract binary adapter
 */
abstract class Binary extends AbstractBinary
{
    /**
     * Try to get the version of the binary. If the detection fails, return null
     *
     * @return string|null
     */
    public function getVersion()
    {
        try {
            $output = $this->command(array('--version'), true);
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException(sprintf(
                '%s failed to run command', $this->getName()
            ), $e->getCode(), $e);
        }

        preg_match('/[a-z]+\ -\ part of swftools ([0-9\.]+)/i', $output, $matches);

        return count($matches) > 0 ? $matches[1] : null;
    }
}
