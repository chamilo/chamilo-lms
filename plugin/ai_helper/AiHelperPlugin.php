<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\AiHelper\Requests;
use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__.'/src/deepseek/DeepSeek.php';
/**
 * Description of AiHelperPlugin.
 *
 * @author Christian Beeznest
 */
class AiHelperPlugin extends Plugin
{
    public const TABLE_REQUESTS = 'plugin_ai_helper_requests';
    public const OPENAI_API = 'openai';
    public const DEEPSEEK_API = 'deepseek';

    protected function __construct()
    {
        $version = '1.2';
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
            'tool_lp_enable' => 'boolean',
            'tool_quiz_enable' => 'boolean',
            'tokens_limit' => 'text',
        ];

        parent::__construct($version, $author, $settings);
    }

    /**
     * Get the list of APIs available.
     *
     * @return array
     */
    public function getApiList()
    {
        $list = [
            self::OPENAI_API => 'OpenAI',
            self::DEEPSEEK_API => 'DeepSeek',
        ];

        return $list;
    }

    /**
     * Get the completion text from the selected API.
     *
     * @return string|array
     */
    public function getCompletionText(string $prompt, string $toolName)
    {
        if (!$this->validateUserTokensLimit(api_get_user_id())) {
            return [
                'error' => true,
                'message' => $this->get_lang('ErrorTokensLimit'),
            ];
        }

        $apiName = $this->get('api_name');

        switch ($apiName) {
            case self::OPENAI_API:
                return $this->openAiGetCompletionText($prompt, $toolName);
            case self::DEEPSEEK_API:
                return $this->deepSeekGetCompletionText($prompt, $toolName);
            default:
                return [
                    'error' => true,
                    'message' => 'API not supported.',
                ];
        }
    }

    /**
     * Get completion text from OpenAI.
     */
    public function openAiGetCompletionText(string $prompt, string $toolName)
    {
        try {
            require_once __DIR__.'/src/openai/OpenAi.php';

            $apiKey = $this->get('api_key');
            $organizationId = $this->get('organization_id');

            $ai = new OpenAi($apiKey, $organizationId);

            $params = [
                'model' => 'gpt-3.5-turbo-instruct',
                'prompt' => $prompt,
                'temperature' => 0.2,
                'max_tokens' => 2000,
                'frequency_penalty' => 0,
                'presence_penalty' => 0.6,
                'top_p' => 1.0,
            ];

            $complete = $ai->completion($params);
            $result = json_decode($complete, true);

            if (isset($result['error'])) {
                $errorMessage = $result['error']['message'] ?? 'Unknown error';
                error_log("OpenAI Error: $errorMessage");

                return [
                    'error' => true,
                    'message' => $errorMessage,
                ];
            }

            $resultText = $result['choices'][0]['text'] ?? '';

            if (!empty($resultText)) {
                $this->saveRequest([
                    'user_id' => api_get_user_id(),
                    'tool_name' => $toolName,
                    'prompt' => $prompt,
                    'prompt_tokens' => (int) ($result['usage']['prompt_tokens'] ?? 0),
                    'completion_tokens' => (int) ($result['usage']['completion_tokens'] ?? 0),
                    'total_tokens' => (int) ($result['usage']['total_tokens'] ?? 0),
                ]);
            }

            return $resultText ?: 'No response generated.';
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => 'An error occurred while connecting to OpenAI: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get completion text from DeepSeek.
     */
    public function deepSeekGetCompletionText(string $prompt, string $toolName)
    {
        $apiKey = $this->get('api_key');

        $url = 'https://api.deepseek.com/chat/completions';

        $payload = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => ($toolName === 'quiz')
                        ? 'You are a helpful assistant that generates Aiken format questions.'
                        : 'You are a helpful assistant that generates learning path contents.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'stream' => false,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer $apiKey",
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            error_log('cURL error: '.curl_error($ch));
            curl_close($ch);

            return ['error' => true, 'message' => 'Request to AI provider failed.'];
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['error'])) {
            return [
                'error' => true,
                'message' => $result['error']['message'] ?? 'Unknown error',
            ];
        }

        $resultText = $result['choices'][0]['message']['content'] ?? '';
        $this->saveRequest([
            'user_id' => api_get_user_id(),
            'tool_name' => $toolName,
            'prompt' => $prompt,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
        ]);

        return $resultText;
    }

    /**
     * Generate questions based on the selected AI provider.
     *
     * @param int    $nQ           Number of questions
     * @param string $lang         Language for the questions
     * @param string $topic        Topic of the questions
     * @param string $questionType Type of questions (e.g., 'multiple_choice')
     *
     * @throws Exception If an error occurs
     *
     * @return string Questions generated in Aiken format
     */
    public function generateQuestions(int $nQ, string $lang, string $topic, string $questionType = 'multiple_choice'): string
    {
        $apiName = $this->get('api_name');

        switch ($apiName) {
            case self::OPENAI_API:
                return $this->generateOpenAiQuestions($nQ, $lang, $topic, $questionType);
            case self::DEEPSEEK_API:
                return $this->generateDeepSeekQuestions($nQ, $lang, $topic, $questionType);
            default:
                throw new Exception("Unsupported API provider: $apiName");
        }
    }

    /**
     * Validates tokens limit of a user per current month.
     */
    public function validateUserTokensLimit(int $userId): bool
    {
        $em = Database::getManager();
        $repo = $em->getRepository('ChamiloPluginBundle:AiHelper\Requests');

        $startDate = api_get_utc_datetime(
            null,
            false,
            true)
            ->modify('first day of this month')->setTime(00, 00, 00)
        ;
        $endDate = api_get_utc_datetime(
            null,
            false,
            true)
            ->modify('last day of this month')->setTime(23, 59, 59)
        ;

        $qb = $repo->createQueryBuilder('e')
            ->select('sum(e.totalTokens) as total')
            ->andWhere('e.requestedAt BETWEEN :dateMin AND :dateMax')
            ->andWhere('e.userId = :user')
            ->setMaxResults(1)
            ->setParameters(
                [
                    'dateMin' => $startDate->format('Y-m-d h:i:s'),
                    'dateMax' => $endDate->format('Y-m-d h:i:s'),
                    'user' => $userId,
                ]
            );
        $result = $qb->getQuery()->getOneOrNullResult();
        $totalTokens = !empty($result) ? (int) $result['total'] : 0;

        $valid = true;
        $tokensLimit = $this->get('tokens_limit');
        if (!empty($tokensLimit)) {
            $valid = ($totalTokens <= (int) $tokensLimit);
        }

        return $valid;
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
     * Save user information of openai request.
     *
     * @return int
     */
    public function saveRequest(array $values)
    {
        $em = Database::getManager();

        $objRequest = new Requests();
        $objRequest
            ->setUserId($values['user_id'])
            ->setToolName($values['tool_name'])
            ->setRequestedAt(new DateTime())
            ->setRequestText($values['prompt'])
            ->setPromptTokens($values['prompt_tokens'])
            ->setCompletionTokens($values['completion_tokens'])
            ->setTotalTokens($values['total_tokens'])
        ;
        $em->persist($objRequest);
        $em->flush();

        return $objRequest->getId();
    }

    /**
     * Install the plugin. Set the database up.
     */
    public function install()
    {
        $em = Database::getManager();

        if ($em->getConnection()->getSchemaManager()->tablesExist([self::TABLE_REQUESTS])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema(
            [
                $em->getClassMetadata(Requests::class),
            ]
        );
    }

    /**
     * Unistall plugin. Clear the database.
     */
    public function uninstall()
    {
        $em = Database::getManager();

        if (!$em->getConnection()->getSchemaManager()->tablesExist([self::TABLE_REQUESTS])) {
            return;
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(
            [
                $em->getClassMetadata(Requests::class),
            ]
        );
    }

    /**
     * Generate questions using OpenAI.
     */
    private function generateOpenAiQuestions(int $nQ, string $lang, string $topic, string $questionType): string
    {
        $prompt = sprintf(
            'Generate %d "%s" questions in Aiken format in the %s language about "%s", making sure there is a \'ANSWER\' line for each question. \'ANSWER\' lines must only mention the letter of the correct answer, not the full answer text and not a parenthesis. The line starting with \'ANSWER\' must not be separated from the last possible answer by a blank line. Each answer starts with an uppercase letter, a dot, one space and the answer text without quotes. Include an \'ANSWER_EXPLANATION\' line after the \'ANSWER\' line for each question. The terms between single quotes above must not be translated. There must be a blank line between each question.',
            $nQ,
            $questionType,
            $lang,
            $topic
        );

        $result = $this->openAiGetCompletionText($prompt, 'quiz');
        if (isset($result['error']) && true === $result['error']) {
            throw new Exception($result['message']);
        }

        return $result;
    }

    /**
     * Generate questions using DeepSeek.
     */
    private function generateDeepSeekQuestions(int $nQ, string $lang, string $topic, string $questionType): string
    {
        $apiKey = $this->get('api_key');
        $prompt = sprintf(
            'Generate %d "%s" questions in Aiken format in the %s language about "%s", making sure there is a \'ANSWER\' line for each question. \'ANSWER\' lines must only mention the letter of the correct answer, not the full answer text and not a parenthesis. The line starting with \'ANSWER\' must not be separated from the last possible answer by a blank line. Each answer starts with an uppercase letter, a dot, one space and the answer text without quotes. Include an \'ANSWER_EXPLANATION\' line after the \'ANSWER\' line for each question. The terms between single quotes above must not be translated. There must be a blank line between each question.',
            $nQ,
            $questionType,
            $lang,
            $topic
        );
        $payload = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that generates Aiken format questions.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'stream' => false,
        ];

        $deepSeek = new DeepSeek($apiKey);
        $response = $deepSeek->generateQuestions($payload);

        return $response;
    }
}
