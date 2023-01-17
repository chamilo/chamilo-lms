<?php
/* For license terms, see /license.txt */

/**
 * Description of AiHelperPlugin.
 *
 * @author Christian Beeznest <christian.fasanando@beeznest.com>
 */
class AiHelperPlugin extends Plugin
{
    public const OPENAI_API = 'openai';

    protected function __construct()
    {
        $version = '1.0';
        $author = 'Christian Beeznest';

        $message = 'Description';

        $settings = [
            $message => 'html',
            'tool_enable' => 'boolean',
            'api_name' => [
                'type' => 'select',
                'options' => $this->getApiList(),
            ],
            'api_key' => 'text',
            'organization_id' => 'text',
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
        $list = [
            self::OPENAI_API => 'OpenAI',
        ];

        return $list;
    }

    /**
     * Get the plugin directory name.
     */
    public function get_name(): string
    {
        return 'ai_helper';
    }

    /**
     * Get the class instance.
     *
     * @staticvar AiHelperPlugin $result
     */
    public static function create(): AiHelperPlugin
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Install the plugin. Set the database up.
     */
    public function install()
    {
    }

    /**
     * Unistall plugin. Clear the database.
     */
    public function uninstall()
    {
    }
}
