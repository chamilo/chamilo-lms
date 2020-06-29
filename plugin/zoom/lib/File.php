<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

class File extends API\RecordingFile
{
    /** @var string */
    public $formattedFileSize;

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function initializeExtraProperties()
    {
        parent::initializeExtraProperties();
        $this->formattedFileSize = format_file_size($this->file_size);
    }
}
