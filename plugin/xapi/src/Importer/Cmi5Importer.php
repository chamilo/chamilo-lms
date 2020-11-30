<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use Chamilo\CoreBundle\Entity\Course;

/**
 * Class Cmi5Importer
 *
 * @package Chamilo\PluginBundle\XApi\Importer
 */
class Cmi5Importer extends AbstractImporter
{
    /**
     * @inheritDoc
     */
    public static function create(array $fileInfo, Course $course)
    {
        return new self($fileInfo, 'cmi5', $course);
    }

    /**
     * {@inheritdoc}
     */
    protected function validPackage()
    {
        parent::validPackage();

        $zipContent = $this->zipFile->listContent();

        $isValid = false;

        foreach ($zipContent as $zipEntry) {
            if ('cmi5.xml' === $zipEntry['filename']) {
                $isValid = true;
            }
        }

        if (!$isValid) {
            throw new \Exception('Incorrect package. Missing "cmi5.xml" file');
        }
    }
}
