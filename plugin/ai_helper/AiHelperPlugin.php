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
        $author = 'Christian Fasanando';

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
     * Get the completion text from openai.
     *
     * @return string
     */
    public function openAiGetCompletionText(string $prompt)
    {
        require_once __DIR__.'/src/openai/OpenAi.php';

        $apiKey = $this->get('api_key');
        $organizationId = $this->get('organization_id');

        $ai = new OpenAi($apiKey, $organizationId);

        $temperature = 0.2;
        $model = 'text-davinci-003';
        $maxTokens = 2000;
        $frequencyPenalty = 0;
        $presencePenalty = 0.6;
        $topP = 1.0;

        $complete = $ai->completion([
            'model' => $model,
            'prompt' => $prompt,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'frequency_penalty' => $frequencyPenalty,
            'presence_penalty' => $presencePenalty,
            'top_p' => $topP,
        ]);

        $result = json_decode($complete, true);
        $resultText = '';
        if (!empty($result['choices'])) {
            $resultText = trim($result['choices'][0]['text']);
        }

        return $resultText;
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
