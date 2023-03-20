<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class CreateUploadedFile
{
    public static function fromString(string $fileName, string $mimeType, string $content): UploadedFile
    {
        /*$handle = tmpfile();
        fwrite($handle, $content);
        $meta = stream_get_meta_data($handle);*/

        $tmpFilename = tempnam(sys_get_temp_dir(), 'resource_file_');
        file_put_contents($tmpFilename, $content);

        return new UploadedFile($tmpFilename, $fileName, $mimeType, null, true);
    }
}
