<?php

namespace Behat\MinkExtension\Context;

use Behat\Behat\Context\BehatContext;

use Behat\Mink\Mink,
    Behat\Mink\WebAssert,
    Behat\Mink\Session;

/*
 * This file is part of the Behat\MinkExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Raw Mink context for Behat BDD tool.
 * Provides raw Mink integration (without step definitions) and web assertions.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class RawMinkContext extends BehatContext implements MinkAwareInterface
{
    private $mink;
    private $minkParameters;

    /**
     * Sets Mink instance.
     *
     * @param Mink $mink Mink session manager
     */
    public function setMink(Mink $mink)
    {
        $this->mink = $mink;
    }

    /**
     * Returns Mink instance.
     *
     * @return Mink
     */
    public function getMink()
    {
        return $this->mink;
    }

    /**
     * Returns the parameters provided for Mink.
     *
     * @return array
     */
    public function getMinkParameters()
    {
        return $this->minkParameters;
    }

    /**
     * Sets parameters provided for Mink.
     *
     * @param array $parameters
     */
    public function setMinkParameters(array $parameters)
    {
        $this->minkParameters = $parameters;
    }

    /**
     * Returns specific mink parameter.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getMinkParameter($name)
    {
        return isset($this->minkParameters[$name]) ? $this->minkParameters[$name] : null;
    }

    /**
     * Applies the given parameter to the Mink configuration. Consider that all parameters get reset for each
     * feature context.
     *
     * @param string $name  The key of the parameter
     * @param string $value The value of the parameter
     */
    public function setMinkParameter($name, $value)
    {
        $this->minkParameters[$name] = $value;
    }

    /**
     * Returns Mink session.
     *
     * @param string|null $name name of the session OR active session will be used
     *
     * @return Session
     */
    public function getSession($name = null)
    {
        return $this->getMink()->getSession($name);
    }

    /**
     * Returns Mink session assertion tool.
     *
     * @param string|null $name name of the session OR active session will be used
     *
     * @return WebAssert
     */
    public function assertSession($name = null)
    {
        return $this->getMink()->assertSession($name);
    }

    /**
     * Locates url, based on provided path.
     * Override to provide custom routing mechanism.
     *
     * @param string $path
     *
     * @return string
     */
    public function locatePath($path)
    {
        $startUrl = rtrim($this->getMinkParameter('base_url'), '/') . '/';

        return 0 !== strpos($path, 'http') ? $startUrl . ltrim($path, '/') : $path;
    }

    /**
     * Save a screenshot of the current window to the file system.
     *
     * @param  string  $filename Desired filename, defaults to
     *   <browser_name>_<ISO 8601 date>_<randomId>.png
     * @param  string  $filepath Desired filepath, defaults to
     *   upload_tmp_dir, falls back to sys_get_temp_dir()
     */
    public function saveScreenshot($filename = null, $filepath = null)
    {
        // Under Cygwin, uniqid with more_entropy must be set to true.
        // No effect in other environments.
        $filename = $filename ?: sprintf('%s_%s_%s.%s', $this->getMinkParameter('browser_name'), date('c'), uniqid('', true), 'png');
        $filepath = $filepath ? $filepath : (ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir());
        file_put_contents($filepath . '/' . $filename, $this->getSession()->getScreenshot());
    }
}
