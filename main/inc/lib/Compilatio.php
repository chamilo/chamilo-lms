<?php

/* For licensing terms, see /license.txt */

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;

/**
 * Build the communication with the SOAP server Compilatio.net
 * call several methods for the file management in Compilatio.net.
 *
 * @version: 2.0
 */
class Compilatio
{
    /** Identification key for the Compilatio account*/
    public $key;

    /**
     * @var Client
     */
    public $client;
    /**
     * @var string
     */
    protected $baseUrl;
    /** Webservice connection*/
    private $maxFileSize;
    private $proxyHost;
    private $proxyPort;

    /**
     * Compilatio constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $settings = $this->getSettings();

        $this->maxFileSize = $settings['max_filesize'];
        $this->key = $settings['key'];
        $this->baseUrl = $settings['api_url'];

        if (!empty($settings['proxy_host'])) {
            $this->proxyHost = $settings['proxy_host'];
            $this->proxyPort = $settings['proxy_port'];
        }

        $clientConfig = [
            'base_uri' => api_remove_trailing_slash($this->baseUrl).'/',
            'headers' => [
                'X-Auth-Token' => $this->key,
                'Accept' => 'application/json',
            ],
        ];

        if ($this->proxyPort) {
            $clientConfig['proxy'] = $this->proxyHost.':'.$this->proxyPort;
        }

        $this->client = new Client($clientConfig);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     *
     * @return Compilatio
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }

    /**
     * @return mixed
     */
    public function getProxyHost()
    {
        return $this->proxyHost;
    }

    /**
     * @return mixed
     */
    public function getProxyPort()
    {
        return $this->proxyPort;
    }

    /**
     * Method for the file load.
     */
    public function sendDoc(
        string $title,
        string $description,
        string $filename,
        string $filepath
    ) {
        $user = api_get_user_entity(api_get_user_id());

        $postData = [
            'folder_id' => '',
            'title' => $title,
            'filename' => basename($filename),
            'indexed' => 'true',
            'user_notes' => [
                'description' => $description,
            ],
            'authors' => [
                [
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getlastname(),
                    'email_address' => $user->getEmail(),
                ],
            ],
            'depositor' => [
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getlastname(),
                'email_address' => $user->getEmail(),
            ],
        ];

        try {
            $responseBody = $this->client
                ->post(
                    'private/documents',
                    [
                        'multipart' => [
                            [
                                'name' => 'postData',
                                'contents' => json_encode($postData),
                            ],
                            [
                                'name' => 'file',
                                'contents' => Utils::tryFopen($filepath, 'r'),
                            ],
                        ],
                    ]
                )
                ->getBody()
            ;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $body = json_decode((string) $responseBody, true);

        return $body['data']['document']['id'];
    }

    /**
     * Method for recover a document's information.
     *
     * @throws Exception
     */
    public function getDoc(string $documentId): array
    {
        try {
            $responseBody = $this->client
                ->get(
                    "private/documents/$documentId"
                )
                ->getBody()
            ;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $responseJson = json_decode((string) $responseBody, true);
        $dataDocument = $responseJson['data']['document'];

        $documentInfo = [
            'report_url' => $dataDocument['report_url'],
        ];
        // anasim analyse type is applied for services Magister and Copyright
        // anasim-premium analyse type is applied for services Magister+ and Copyright+
        $anasim = 'anasim';
        if (isset($dataDocument['analyses']['anasim-premium'])) {
            $anasim = 'anasim-premium';
            if (isset($dataDocument['analyses']['anasim'])) {
                if (isset($dataDocument['analyses']['anasim']['creation_launch_date']) && isset($dataDocument['analyses']['anasim-premium']['creation_launch_date'])) {
                    // if the 2 analyses type exist (which could happen technically but would be exceptional) then we present the most recent one.
                    if ($dataDocument['analyses']['anasim']['creation_launch_date'] > $dataDocument['analyses']['anasim-premium']['creation_launch_date']) {
                        $anasim = 'anasim';
                    }
                }
            }
        }
        if (isset($dataDocument['analyses'][$anasim]['state'])) {
            $documentInfo['analysis_status'] = $dataDocument['analyses'][$anasim]['state'];
        }

        if (isset($dataDocument['light_reports'][$anasim]['scores']['global_score_percent'])) {
            $documentInfo['report_percent'] = $dataDocument['light_reports'][$anasim]['scores']['global_score_percent'];
        }

        return $documentInfo;
    }

    /**
     *  Method for deleting a Compialtio's account document.
     */
    public function deldoc(string $documentId)
    {
    }

    /**
     * Method for start the analysis for a document.
     *
     * @throws Exception
     */
    public function startAnalyse(string $compilatioId): string
    {
        try {
            $responseBody = $this->client
                ->post(
                    'private/analyses',
                    [
                        'json' => [
                            'doc_id' => $compilatioId,
                        ],
                    ]
                )
                ->getBody()
            ;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $body = json_decode((string) $responseBody, true);

        return $body['data']['analysis']['state'];
    }

    /**
     * Method for identify a file extension and the possibility that the document can be managed by Compilatio.
     */
    public static function verifiFileType(string $filename): bool
    {
        $types = ['doc', 'docx', 'rtf', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'pdf', 'txt', 'htm', 'html'];
        $extension = substr($filename, strrpos($filename, '.') + 1);
        $extension = strtolower($extension);

        return in_array($extension, $types);
    }

    /**
     * Method for display the PomprseuilmankBar (% de plagiat).
     */
    public static function getPomprankBarv31(
        int $index,
        int $weakThreshold,
        int $highThreshold
    ): string {
        $index = round($index);
        $class = 'danger';
        if ($index < $weakThreshold) {
            $class = 'success';
        } elseif ($index < $highThreshold) {
            $class = 'warning';
        }

        return Display::bar_progress($index, true, null, $class);
    }

    /**
     * Function for delete a document of the compilatio table if plagiarismTool is Compilatio.
     */
    public static function plagiarismDeleteDoc(int $courseId, int $itemId)
    {
        if (api_get_configuration_value('allow_compilatio_tool') !== false) {
            $table = Database::get_course_table(TABLE_PLAGIARISM);
            $params = [$courseId, $itemId];
            Database::delete($table, ['c_id = ? AND document_id = ?' => $params]);
        }
    }

    public function saveDocument(int $courseId, int $documentId, string $compilatioId)
    {
        $table = Database::get_course_table(TABLE_PLAGIARISM);
        $params = [
            'c_id' => $courseId,
            'document_id' => $documentId,
            'compilatio_id' => $compilatioId,
        ];
        Database::insert($table, $params);
    }

    public function getCompilatioId(int $documentId, int $courseId): ?string
    {
        $table = Database::get_course_table(TABLE_PLAGIARISM);
        $sql = "SELECT compilatio_id FROM $table
                WHERE document_id = $documentId AND c_id= $courseId";
        $result = Database::query($sql);
        $result = Database::fetch_object($result);

        return $result ? (string) $result->compilatio_id : null;
    }

    public function giveWorkIdState(int $workId): string
    {
        $courseId = api_get_course_int_id();
        $compilatioId = $this->getCompilatioId($workId, $courseId);

        $actionCompilatio = '';
        // if the compilatio's hash is not a valide hash md5,
        // we return à specific status (cf : IsInCompilatio() )
        // Not used since implementation of RestAPI but there if needed later
        //$actionCompilatio = get_lang('CompilatioDocumentTextNotImage').'<br/>'.
        //    get_lang('CompilatioDocumentNotCorrupt');
        $status = '';
        if (!empty($compilatioId)) {
            // if compilatio_id is a hash md5, we call the function of the compilatio's
            // webservice who return the document's status
            $soapRes = $this->getDoc($compilatioId);
            $status = $soapRes['analysis_status'] ?? '';

            $spinnerIcon = Display::returnFontAwesomeIcon('spinner', null, true, 'fa-spin');

            switch ($status) {
                case 'finished':
                    $actionCompilatio .= self::getPomprankBarv31($soapRes['report_percent'], 10, 35)
                        .PHP_EOL
                        .Display::url(
                            get_lang('CompilatioAnalysis'),
                            $soapRes['report_url'],
                            ['class' => 'btn btn-primary btn-xs', 'target' => '_blank']
                        );
                    break;
                case 'running':
                    $actionCompilatio .= "<div style='font-weight:bold;text-align:left'>"
                        .get_lang('CompilatioAnalysisInProgress')
                        ."</div>";
                    $actionCompilatio .= "<div style='font-size:80%;font-style:italic;margin-bottom:5px;'>"
                        .get_lang('CompilatioAnalysisPercentage')
                        ."</div>";
                    $actionCompilatio .= $spinnerIcon.PHP_EOL.get_lang('CompilatioAnalysisEnding');
                    break;
                case 'waiting':
                    $actionCompilatio .= $spinnerIcon.PHP_EOL.get_lang('CompilatioWaitingAnalysis');
                    break;
                case 'canceled':
                    $actionCompilatio .= get_lang('Cancelled');
                    break;
                case 'scheduled':
                    $actionCompilatio .= $spinnerIcon.PHP_EOL.get_lang('CompilatioAwaitingAnalysis');
                    break;
            }
        }

        return $workId.'|'.$actionCompilatio.'|'.$status.'|';
    }

    /**
     * @throws Exception
     */
    protected function getSettings(): array
    {
        if (empty(api_get_configuration_value('allow_compilatio_tool')) ||
            empty(api_get_configuration_value('compilatio_tool'))
        ) {
            throw new Exception('Compilatio not available');
        }

        $compilatioTool = api_get_configuration_value('compilatio_tool');

        if (!isset($compilatioTool['settings'])) {
            throw new Exception('Compilatio config available');
        }

        $settings = $compilatioTool['settings'];

        if (empty($settings['key'])) {
            throw new Exception('API key not available');
        }

        if (empty($settings['api_url'])) {
            throw new Exception('Api URL not available');
        }

        return $settings;
    }
}
