<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\AiHelper\Requests;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Description of AiHelperPlugin.
 *
 * @author Christian Beeznest <christian.fasanando@beeznest.com>
 */
class AiHelperPlugin extends Plugin
{
    public const TABLE_REQUESTS = 'plugin_ai_helper_requests';
    public const OPENAI_API = 'openai';

    protected function __construct()
    {
        $version = '1.1';
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
    public function openAiGetCompletionText(
        string $prompt,
        string $toolName
    ) {
        if (!$this->validateUserTokensLimit(api_get_user_id())) {
            return [
                'error' => true,
                'message' => $this->get_lang('ErrorTokensLimit'),
            ];
        }

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
            $resultText = $result['choices'][0]['text'];
            // saves information of user results.
            $values = [
                'user_id' => api_get_user_id(),
                'tool_name' => $toolName,
                'prompt' => $prompt,
                'prompt_tokens' => (int) $result['usage']['prompt_tokens'],
                'completion_tokens' => (int) $result['usage']['completion_tokens'],
                'total_tokens' => (int) $result['usage']['total_tokens'],
            ];
            $this->saveRequest($values);
        }

        return $resultText;
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
}
