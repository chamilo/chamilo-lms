<?php
/* For license terms, see /license.txt */

/**
 * Description of Text2SpeechPlugin.
 *
 * @author Francis Gonzales <francis@fragote.com>
 */
class Text2SpeechPlugin extends Plugin
{
    public const MOZILLATTS_API = 'mozillatts';
    public const PATH_TO_SAVE_FILES = __DIR__.'/../../app/upload/plugins/text2speech/';

    protected function __construct()
    {
        $version = '0.1';
        $author = 'Francis Gonzales';

        $message = '<p>'.$this->get_lang('plugin_comment').'</p>';

        $settings = [
            $message => 'html',
            'tool_enable' => 'boolean',
            'api_name' => [
                'type' => 'select',
                'options' => $this->getApiList(),
            ],
            'api_key' => 'text',
            'url' => 'text',
            'tool_lp_enable' => 'boolean',
        ];

        parent::__construct($version, $author, $settings);
    }

    /**
     * Get the list of apis availables.
     *
     * @return array
     */
    public function getApiList()
    {
        return [
            self::MOZILLATTS_API => 'MozillaTTS',
        ];
    }

    /**
     * Get the completion text from openai.
     *
     * @return string
     */
    public function convert(string $text)
    {
        $path = '/app/upload/plugins/text2speech/';
        switch ($this->get('api_name')) {
            case self::MOZILLATTS_API:
                require_once __DIR__.'/src/mozillatts/MozillaTTS.php';

                $mozillaTTS = new MozillaTTS($this->get('url'), $this->get('api_key'), self::PATH_TO_SAVE_FILES);
                $path .= $mozillaTTS->convert($text);
            break;
        }

        return $path;
    }

    /**
     * Get the plugin directory name.
     */
    public function get_name(): string
    {
        return 'text2speech';
    }

    /**
     * Get the class instance.
     *
     * @staticvar Text2SpeechPlugin $result
     */
    public static function create(): Text2SpeechPlugin
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Install the plugin. create folder to save files.
     */
    public function install()
    {
        if (!file_exists(self::PATH_TO_SAVE_FILES)) {
            mkdir(self::PATH_TO_SAVE_FILES);
        }
    }

    /**
     * Unistall plugin. Clear the folder.
     */
    public function uninstall()
    {
        if (file_exists(self::PATH_TO_SAVE_FILES)) {
            array_map('unlink', glob(self::PATH_TO_SAVE_FILES.'/*.*'));
            rmdir(self::PATH_TO_SAVE_FILES);
        }
    }
}
