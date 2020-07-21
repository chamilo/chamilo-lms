<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Exception;

/**
 * Class File. A RecordingFile with extra help properties for the web view.
 *
 * @package Chamilo\PluginBundle\Zoom
 */
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
