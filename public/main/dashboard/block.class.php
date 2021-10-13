<?php
/* For licensing terms, see /license.txt */

/**
 * Class Block
 * This file contains class used parent class for blocks plugins
 * Parent class for controller Blocks from dashboard plugin.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 */
class Block
{
    protected $path;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    public function getDeleteLink(): string
    {
        global $charset;
        $closeLink = '<a class="btn btn-danger btn-xs" onclick="javascript:if(!confirm(\''.addslashes(
                api_htmlentities(
                    get_lang('Please confirm your choice'),
                    ENT_QUOTES
                )
            ).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">
                <em class="fa fa-times"></em>
            </a>';

        return $closeLink;
    }

    /**
     * @param string $title
     * @param string $content
     */
    public function getBlockCard($title, $content): string
    {
        $html = Display::panel(
            $title,
            $content,
            '',
            'default',
            '',
            '',
            '',
            $this->getDeleteLink()
        );

        return $html;
    }
}
