<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Traits\MapTrait;

trait MapTrait
{
    /**
     * @var string
     */
    protected $calledClass;

    /**
     * @return string
     */
    private function getMapFileName()
    {
        $name = substr(strrchr($this->calledClass, '\\'), 1);

        return  api_camel_case_to_underscore($name);
    }

    /**
     * @return string
     */
    private function getMapFilePath()
    {
        $name = $this->getMapFileName();

        $dirPath = __DIR__.'/../../map';

        return "$dirPath/$name.json";
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    private function parseMapFile()
    {
        $filePath = $this->getMapFilePath();

        $contents = @file_get_contents($filePath);

        if (false === $contents) {
            throw new \Exception("Failed to read $filePath file.");
        }

        /** @var array $mapLog */
        $mapLog = json_decode($contents, true);

        return $mapLog ?: [];
    }
}
