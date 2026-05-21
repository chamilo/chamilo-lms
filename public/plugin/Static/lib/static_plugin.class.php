<?php

/**
 * Static content plugin.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class StaticPlugin extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '1.1',
            'Laurent Opprecht',
            [
                'block_title' => 'text',
                'content' => 'wysiwyg',
            ]
        );
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function get_info(): array
    {
        $info = parent::get_info();

        $info['supports_regions'] = true;

        return $info;
    }

    public function get_block_title(): string
    {
        return (string) $this->get('block_title');
    }

    public function get_content(): string
    {
        return (string) $this->get('content');
    }

    public function get_css(): string
    {
        $path = api_get_path(SYS_PLUGIN_PATH).'Static/resources/static.css';

        if (!is_readable($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }
}
