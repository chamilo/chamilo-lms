<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Importer;

use Chamilo\CoreBundle\Entity\Course;
use Exception;

/**
 * Class TinCanImporter.
 *
 * @package Chamilo\PluginBundle\XApi\Importer
 */
class TinCanImporter extends AbstractImporter
{
    /**
     * {@inheritdoc}
     */
    public static function create(array $fileInfo, Course $course)
    {
        return new self($fileInfo, 'tincan', $course);
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
            if ('tincan.xml' === $zipEntry['filename']) {
                $isValid = true;
            }
        }

        if (!$isValid) {
            throw new Exception('Incorrect package. Missing "tincan.xml" file');
        }
    }
}
