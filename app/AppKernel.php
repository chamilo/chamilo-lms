<?php
/* For licensing terms, see /license.txt */

/**
 * Class AppKernel
 */
class AppKernel
{
    protected $rootDir;

    /**
     * @return string
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $r = new \ReflectionObject($this);
            $this->rootDir = str_replace('\\', '/', dirname($r->getFileName()));
        }

        return $this->rootDir;
    }

    /*public function getCacheDir()
    {
        return dirname(dirname(__DIR__)).'/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return dirname(dirname(__DIR__)).'/log/';
    }*/

    /*public function getLogDir()
    {
        return $this->rootDir.'/../logs/'.$this->environment.'/logs/';
    }

    public function getCacheDir()
    {
        return $this->rootDir.'/../data/temp/'.$this->environment.'/cache/';
    }*/

    // Custom paths

    /**
     * Returns the real root path
     * @return string
     */
    public function getRealRootDir()
    {
        return realpath($this->getRootDir().'/../').'/';
    }

    /**
     * Returns the data path
     * @return string
     */
    public function getDataDir()
    {
        return $this->getRealRootDir().'data/';
    }

    /**
     * @return string
     */
    public function getAppDir()
    {
        return $this->getRealRootDir().'app/';
    }

    /**
     * @return string
     */
    public function getConfigDir()
    {
        return $this->getRealRootDir().'app/config/';
    }

    /**
     * @return string
     */
    public function getConfigurationFile()
    {
        return $this->getRealRootDir().'app/config/configuration.php';
    }
}

