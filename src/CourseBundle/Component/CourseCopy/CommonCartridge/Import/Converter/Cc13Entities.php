<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Converter;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Cc1p3Convert;

use const DIRECTORY_SEPARATOR;
use const PATHINFO_EXTENSION;

class Cc13Entities extends CcEntities
{
    public function getExternalXml($identifier)
    {
        $xpath = Cc1p3Convert::newxPath(Cc1p3Convert::$manifest, Cc1p3Convert::$namespaces);
        $files = $xpath->query(
            '/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.$identifier.'"]/imscc:file/@href'
        );

        return ($files && $files->length > 0) ? $files->item(0)->nodeValue : '';
    }

    protected function getAllFiles(): array
    {
        // Permissions helper returned by Chamilo can be an int or an array.
        $permDirs = api_get_permissions_for_new_directories();
        $permMode = \is_int($permDirs)
            ? $permDirs
            : (\is_array($permDirs) && isset($permDirs['directory']) ? (int) $permDirs['directory'] : 0775);

        $allFiles = [];
        $xpath = Cc1p3Convert::newxPath(Cc1p3Convert::$manifest, Cc1p3Convert::$namespaces);

        foreach (Cc1p3Convert::$restypes as $type) {
            $files = $xpath->query(
                '/imscc:manifest/imscc:resources/imscc:resource[@type="'.$type.'"]/imscc:file/@href'
            );
            if (empty($files) || (0 === $files->length)) {
                continue;
            }
            foreach ($files as $file) {
                // Omit HTML-like files (simple heuristic).
                $ext = strtolower(pathinfo($file->nodeValue, PATHINFO_EXTENSION) ?: '');
                if (\in_array($ext, ['html', 'htm', 'xhtml'], true)) {
                    continue;
                }
                $allFiles[] = $file->nodeValue;
            }
            unset($files);
        }

        // If there are label-only items, ensure a generic icon is present.
        $xquery = '//imscc:item/imscc:item/imscc:item[imscc:title][not(@identifierref)]';
        $labels = $xpath->query($xquery);
        if (!empty($labels) && ($labels->length > 0)) {
            $tname = 'course_files';
            $dpath = Cc1p3Convert::$pathToManifestFolder.DIRECTORY_SEPARATOR.$tname;
            $rfpath = 'files.gif';
            if (!file_exists($dpath)) {
                // Create directory if missing.
                @mkdir($dpath, $permMode, true);
            }
            // Just reference the placeholder; actual file may be vendor-provided.
            $allFiles[] = $rfpath;
        }

        return $allFiles;
    }
}
