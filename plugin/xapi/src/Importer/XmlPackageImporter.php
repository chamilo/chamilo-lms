<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use Exception;

/**
 * Class XmlImporter.
 *
 * @package Chamilo\PluginBundle\XApi\Importer
 */
class XmlPackageImporter extends PackageImporter
{
    /**
     * {@inheritDoc}
     */
    public function import(): string
    {
        if (!in_array($this->packageFileInfo['name'], ['tincan.xml', 'cmi5.xml'])) {
            throw new Exception('Invalid package');
        }

        $this->packageType = explode('.', $this->packageFileInfo['name'], 2)[0];

        return $this->packageFileInfo['tmp_name'];
    }
}
