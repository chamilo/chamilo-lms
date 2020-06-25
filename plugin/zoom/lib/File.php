<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class File extends API\RecordingFile
{
    /** @var string */
    public $formattedFileSize;

    /**
     * Makes a File out of a RecordingFile.
     *
     * @param API\RecordingFile $source
     *
     * @throws Exception
     *
     * @return static
     */
    public static function fromRecordingFile($source)
    {
        $instance = new static();
        self::recursivelyCopyObjectProperties($source, $instance);

        $instance->formattedFileSize = format_file_size($instance->file_size);

        return $instance;
    }
}
