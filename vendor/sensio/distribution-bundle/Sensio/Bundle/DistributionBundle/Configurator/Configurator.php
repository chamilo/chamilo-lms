<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\DistributionBundle\Configurator;

use Sensio\Bundle\DistributionBundle\Configurator\Step\StepInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Configurator.
 *
 * @author Marc Weistroff <marc.weistroff@gmail.com>
 */
class Configurator
{
    protected $filename;
    protected $steps;
    protected $parameters;

    public function __construct($kernelDir)
    {
        $this->kernelDir = $kernelDir;
        $this->filename = $kernelDir.'/config/parameters.yml';

        $this->steps = array();
        $this->parameters = $this->read();
    }

    public function isFileWritable()
    {
        return is_writable($this->filename);
    }

    public function clean()
    {
        if (file_exists($this->getCacheFilename())) {
            @unlink($this->getCacheFilename());
        }
    }

    /**
     * @param StepInterface $step
     */
    public function addStep(StepInterface $step)
    {
        $this->steps[] = $step;
    }

    /**
     * @param integer $index
     *
     * @return StepInterface
     */
    public function getStep($index)
    {
        if (isset($this->steps[$index])) {
            return $this->steps[$index];
        }
    }

    /**
     * @return array
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return integer
     */
    public function getStepCount()
    {
        return count($this->steps);
    }

    /**
     * @param array $parameters
     */
    public function mergeParameters($parameters)
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * @return array
     */
    public function getRequirements()
    {
        $majors = array();
        foreach ($this->steps as $step) {
            foreach ($step->checkRequirements() as $major) {
                $majors[] = $major;
            }
        }

        return $majors;
    }

    /**
     * @return array
     */
    public function getOptionalSettings()
    {
        $minors = array();
        foreach ($this->steps as $step) {
            foreach ($step->checkOptionalSettings() as $minor) {
                $minors[] = $minor;
            }
        }

        return $minors;
    }

    /**
     * Renders parameters as a string.
     *
     * @return string
     */
    public function render()
    {
        return Yaml::dump(array('parameters' => $this->parameters));
    }

    /**
     * Writes parameters to parameters.yml or temporary in the cache directory.
     *
     * @return boolean
     */
    public function write()
    {
        $filename = $this->isFileWritable() ? $this->filename : $this->getCacheFilename();

        return file_put_contents($filename, $this->render());
    }

    /**
     * Reads parameters from file.
     *
     * @return array
     */
    protected function read()
    {
        $filename = $this->filename;
        if (!$this->isFileWritable() && file_exists($this->getCacheFilename())) {
            $filename = $this->getCacheFilename();
        }

        $ret = Yaml::parse($filename);
        if (false === $ret || array() === $ret) {
            throw new \InvalidArgumentException(sprintf('The %s file is not valid.', $filename));
        }

        if (isset($ret['parameters']) && is_array($ret['parameters'])) {
            return $ret['parameters'];
        } else {
            return array();
        }
    }

    /**
     * getCacheFilename
     *
     * @return string
     */
    protected function getCacheFilename()
    {
        return $this->kernelDir.'/cache/parameters.yml';
    }
}
