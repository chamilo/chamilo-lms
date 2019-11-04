<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

use Chamilo\CoreBundle\Component\Editor\Connector;

/**
 * Class Driver.
 *
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 */
class Driver extends \elFinderVolumeLocalFileSystem
{
    /** @var string */
    public $name;

    /** @var Connector */
    public $connector;

    /**
     * Gets driver name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets driver name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set connector.
     */
    public function setConnector(Connector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * @return array
     */
    public function getAppPluginOptions()
    {
        return $this->getOptionsPlugin('chamilo');
    }

    /**
     * @return Connector
     */
    public function setConnectorFromPlugin()
    {
        $options = $this->getAppPluginOptions();
        $this->setConnector($options['connector']);
    }

    /**
     * This is a copy of rename function only to be used when uploading a file
     * {@inheritdoc}
     */
    public function customRename($hash, $name)
    {
        if (!$this->nameAccepted($name)) {
            return $this->setError(\elFinder::ERROR_INVALID_NAME, $name);
        }

        if (!($file = $this->file($hash))) {
            return $this->setError(\elFinder::ERROR_FILE_NOT_FOUND);
        }

        if ($name == $file['name']) {
            return $file;
        }

        if (!empty($file['locked'])) {
            return $this->setError(\elFinder::ERROR_LOCKED, $file['name']);
        }

        $path = $this->decode($hash);
        $dir = $this->dirnameCE($path);
        $stat = $this->stat($this->joinPathCE($dir, $name));

        if ($stat) {
            return $this->setError(\elFinder::ERROR_EXISTS, $name);
        }

        if (!$this->allowCreate($dir, $name, ($file['mime'] === 'directory'))) {
            return $this->setError(\elFinder::ERROR_PERM_DENIED);
        }

        $this->rmTmb($file); // remove old name tmbs, we cannot do this after dir move

        if ($path = $this->convEncOut($this->_move($this->convEncIn($path), $this->convEncIn($dir), $this->convEncIn($name)))) {
            $this->clearcache();

            return $this->stat($path);
        }

        return false;
    }
}
