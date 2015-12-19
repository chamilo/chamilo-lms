<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Fixtures\IO;

use Composer\IO\BaseIO;

/**
 * Mock of IO.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class MockIO extends BaseIO
{
    /**
     * @var bool
     */
    protected $verbose;

    /**
     * @var array
     */
    protected $traces;

    /**
     * Constructor.
     *
     * @param bool $verbose
     */
    public function __construct($verbose)
    {
        $this->verbose = $verbose;
        $this->traces = array();
    }

    /**
     * {@inheritdoc}
     */
    public function isInteractive()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isVerbose()
    {
        return $this->verbose;
    }

    /**
     * {@inheritdoc}
     */
    public function isVeryVerbose()
    {
        return $this->verbose;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isDecorated()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = true)
    {
        $pos = max(count($this->traces) - 1, 0);
        if (isset($this->traces[$pos])) {
            $messages = $this->traces[$pos].$messages;
        }
        $this->traces[$pos] = $messages;
        if ($newline) {
            $this->traces[] = '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeError($messages, $newline = true)
    {
        $this->write($messages, $newline);
    }

    /**
     * {@inheritdoc}
     */
    public function overwrite($messages, $newline = true, $size = 80)
    {
        $pos = max(count($this->traces) - 1, 0);
        $this->traces[$pos] = $messages;
        if ($newline) {
            $this->traces[] = '';
        }
    }

    public function overwriteError($messages, $newline = true, $size = null)
    {
        $this->overwrite($messages, $newline, $size);
    }

    /**
     * {@inheritdoc}
     */
    public function ask($question, $default = null)
    {
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function askConfirmation($question, $default = true)
    {
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function askAndValidate($question, $validator, $attempts = false, $default = null)
    {
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function askAndHideAnswer($question)
    {
        return;
    }

    /**
     * Gets the taces.
     *
     * @return array
     */
    public function getTraces()
    {
        return $this->traces;
    }
}
