<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use Exception;

/**
 * Class XmlImporter.
 */
class XmlPackageImporter extends PackageImporter
{
    public function import(): string
    {
        if (!\in_array($this->packageFileInfo['name'], ['tincan.xml', 'cmi5.xml'], true)) {
            throw new Exception('Invalid package');
        }

        $this->packageType = explode('.', $this->packageFileInfo['name'], 2)[0];

        $content = file_get_contents($this->packageFileInfo['tmp_name']);
        if (false === $content) {
            throw new Exception('Unable to read uploaded package.');
        }

        $persistentPrefix = $this->buildPersistentPackagePrefix($this->packageType, $this->packageType);
        $manifestRelativePath = $persistentPrefix.'/'.$this->packageFileInfo['name'];

        $this->getPluginsFilesystem()->write($manifestRelativePath, $content);

        return $this->buildStorageUri($manifestRelativePath);
    }
}
