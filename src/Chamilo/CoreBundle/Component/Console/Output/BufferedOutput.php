<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Console\Output;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BufferedOutput
 * @package Chamilo\CoreBundle\Component\Console\Output
 */
class BufferedOutput extends Output
{
    public $messages = array();
    public $lastMessage = null;
    public $buffer = null;

    /**
     * @param string $message
     * @param bool $newline
     */
    public function doWrite($message, $newline)
    {
        $this->buffer .= $message. '<br />';
        $this->messages[] = $message;
        $this->lastMessage = $message;
    }

    /**
     * @return null
     */
    public function getBuffer()
    {
        return $this->buffer;
    }
}
