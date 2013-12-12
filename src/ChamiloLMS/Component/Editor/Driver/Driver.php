<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\Driver;

use ChamiloLMS\Component\Editor\Connector;

/**
 * Class Driver
 * @package ChamiloLMS\Component\Editor\Driver
 */
class Driver extends \elFinderVolumeLocalFileSystem
{
    /** @var string */
    public $name;

    /** @var Connector */
    public $connector;

    /**
     * Gets driver name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets driver name.
     * @param string
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set connector
     * @param Connector $connector
     */
    public function setConnector(Connector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Get default driver settings.
     * @return array
     */
    private function getDefaultDriverSettings()
    {
        // for more options: https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
        return array(
            'plugin' => array(
                'chamilo' => array(
                    'driverName' => $this->getName(),
                    'connector' => $this->connector,
                )
            ),
            'uploadOverwrite' => false, // Replace files on upload or give them new name if the same file was uploaded
            //'acceptedName' =>
            'uploadAllow' => array(
                'image',
                'audio',
                'video',
                'text/html',
                'text/csv',
                'application/pdf',
                'application/postscript',
                'application/vnd.ms-word',
                'application/vnd.ms-excel',
                'application/vnd.ms-powerpoint',
                'application/pdf',
                'application/xml',
                'application/vnd.oasis.opendocument.text',
                'application/x-shockwave-flash'
            ), # allow files
            //'uploadDeny' => array('text/x-php'),
            'uploadOrder' => array('allow'), // only executes allow
            'disabled' => array(
                'duplicate',
                'rename',
                'mkdir',
                'mkfile',
                'copy',
                'cut',
                'paste',
                'edit',
                'extract',
                'archive',
                'help',
                'resize'
            ),
            'attributes' =>  array(
                // Hiding dangerous files
                array(
                    'pattern' => '/\.(php|py|pl|sh|xml)$/i',
                    'read' => false,
                    'write' => false,
                    'hidden' => true,
                    'locked' => false
                ),
                // Hiding _DELETED_ files
                array(
                    'pattern' => '/_DELETED_/',
                    'read' => false,
                    'write' => false,
                    'hidden' => true,
                    'locked' => false
                ),
                // Hiding thumbnails
                array(
                    'pattern' => '/.tmb/',
                    'read' => false,
                    'write' => false,
                    'hidden' => true,
                    'locked' => false
                ),
                array(
                    'pattern' => '/.thumbs/',
                    'read' => false,
                    'write' => false,
                    'hidden' => true,
                    'locked' => false
                ),
                array(
                    'pattern' => '/.quarantine/',
                    'read' => false,
                    'write' => false,
                    'hidden' => true,
                    'locked' => false
                )
            )
        );
    }

    /**
     * Merges the default driver settings.
     * @param array $driver
     * @return array
     */
    public function updateWithDefaultValues($driver)
    {
        if (empty($driver)) {
            return array();
        }

        $defaultDriver = $this->getDefaultDriverSettings();

        if (isset($driver['attributes'])) {
            $attributes = array_merge($defaultDriver['attributes'], $driver['attributes']);
        } else {
            $attributes = $defaultDriver['attributes'];
        }

        $driverUpdated = array_merge($defaultDriver, $driver);
        $driverUpdated['driver'] = 'ChamiloLMS\Component\Editor\Driver\\'.$driver['driver'];
        $driverUpdated['attributes'] = $attributes;
        return $driverUpdated;
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
}
