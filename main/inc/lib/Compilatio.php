<?php

/* For licensing terms, see /license.txt */

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
    /** Webservice connection*/
    public $soapcli;
    private $transportMode;
    private $maxFileSize;
    private $wgetUri;
    private $wgetLogin;
    private $wgetPassword;
    private $proxyHost;
    private $proxyPort;

    /**
     * Compilatio constructor.
     */
    public function __construct()
    {
        if (empty(api_get_configuration_value('allow_compilatio_tool')) ||
            empty(api_get_configuration_value('compilatio_tool'))
        ) {
            throw new Exception('Compilatio not available');
        }

        $settings = api_get_configuration_value('compilatio_tool');

        if (isset($settings['settings'])) {
            $settings = $settings['settings'];
        } else {
            throw new Exception('Compilatio config available');
        }

        $key = $this->key = $settings['key'];
        $urlsoap = $settings['soap_url'];
        $proxyHost = $this->proxyHost = $settings['proxy_host'];
        $proxyPort = $this->proxyPort = $settings['proxy_port'];
        $this->transportMode = $settings['transport_mode'];
        $this->maxFileSize = $settings['max_filesize'];
        $this->wgetUri = $settings['wget_uri'];
        $this->wgetLogin = $settings['wget_login'];
        $this->wgetPassword = $settings['wget_password'];
        $soapVersion = 2;

        try {
            if (!empty($key)) {
                $this->key = $key;
                if (!empty($urlsoap)) {
                    if (!empty($proxyHost)) {
                        $param = [
                            'trace' => false,
                            'soap_version' => $soapVersion,
                            'exceptions' => true,
                            'proxy_host' => '"'.$proxyHost.'"',
                            'proxy_port' => $proxyPort,
                        ];
                    } else {
                        $param = [
                            'trace' => false,
                            'soap_version' => $soapVersion,
                            'exceptions' => true,
                        ];
                    }
                    $this->soapcli = new SoapClient($urlsoap, $param);
                } else {
                    throw new Exception('WS urlsoap not available');
                }
            } else {
                throw new Exception('API key not available');
            }
        } catch (SoapFault $fault) {
            $this->soapcli = "Error constructor compilatio $fault->faultcode $fault->faultstring ";
        } catch (Exception $e) {
            $this->soapcli = "Error constructor compilatio with urlsoap $urlsoap ".$e->getMessage();
        }
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
    public function getTransportMode()
    {
        return $this->transportMode;
    }

    /**
     * @param mixed $transportMode
     *
     * @return Compilatio
     */
    public function setTransportMode($transportMode)
    {
        $this->transportMode = $transportMode;

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
     * @param mixed $maxFileSize
     *
     * @return Compilatio
     */
    public function setMaxFileSize($maxFileSize)
    {
        $this->maxFileSize = $maxFileSize;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWgetUri()
    {
        return $this->wgetUri;
    }

    /**
     * @param mixed $wgetUri
     *
     * @return Compilatio
     */
    public function setWgetUri($wgetUri)
    {
        $this->wgetUri = $wgetUri;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWgetLogin()
    {
        return $this->wgetLogin;
    }

    /**
     * @param mixed $wgetLogin
     *
     * @return Compilatio
     */
    public function setWgetLogin($wgetLogin)
    {
        $this->wgetLogin = $wgetLogin;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWgetPassword()
    {
        return $this->wgetPassword;
    }

    /**
     * @param mixed $wgetPassword
     *
     * @return Compilatio
     */
    public function setWgetPassword($wgetPassword)
    {
        $this->wgetPassword = $wgetPassword;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProxyHost()
    {
        return $this->proxyHost;
    }

    /**
     * @param mixed $proxyHost
     *
     * @return Compilatio
     */
    public function setProxyHost($proxyHost)
    {
        $this->proxyHost = $proxyHost;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProxyPort()
    {
        return $this->proxyPort;
    }

    /**
     * @param mixed $proxyPort
     *
     * @return Compilatio
     */
    public function setProxyPort($proxyPort)
    {
        $this->proxyPort = $proxyPort;

        return $this;
    }

    /**
     * Method for the file load.
     *
     * @param $title
     * @param $description
     * @param $filename
     * @param $mimeType
     * @param $content
     *
     * @return string
     */
    public function sendDoc($title, $description, $filename, $mimeType, $content)
    {
        try {
            if (!is_object($this->soapcli)) {
                return "Error in constructor compilatio() $this->soapcli";
            }

            return $this->soapcli->__call(
                'addDocumentBase64',
                [
                    $this->key,
                    utf8_encode(urlencode($title)),
                    utf8_encode(urlencode($description)),
                    utf8_encode(urlencode($filename)),
                    utf8_encode($mimeType),
                    base64_encode($content),
                ]
            );
        } catch (SoapFault $fault) {
            return "Erreur sendDoc()".$fault->faultcode." ".$fault->faultstring;
        }
    }

    /**
     * Method for recover a document's information.
     *
     * @param $compiHash
     *
     * @return string
     */
    public function getDoc($compiHash)
    {
        try {
            if (!is_object($this->soapcli)) {
                return "Error in constructor compilatio() ".$this->soapcli;
            }
            $param = [$this->key, $compiHash];

            return $this->soapcli->__call('getDocument', $param);
        } catch (SoapFault $fault) {
            return "Erreur getDoc()".$fault->faultcode." ".$fault->faultstring;
        }
    }

    /**
     * method for recover an url document's report.
     *
     * @param $compiHash
     *
     * @return string
     */
    public function getReportUrl($compiHash)
    {
        try {
            if (!is_object($this->soapcli)) {
                return "Error in constructor compilatio() ".$this->soapcli;
            }
            $param = [$this->key, $compiHash];

            return $this->soapcli->__call('getDocumentReportUrl', $param);
        } catch (SoapFault $fault) {
            return "Erreur  getReportUrl()".$fault->faultcode." ".$fault->faultstring;
        }
    }

    /**
     *  Method for deleting a Compialtio's account document.
     *
     * @param $compiHash
     *
     * @return string
     */
    public function deldoc($compiHash)
    {
        try {
            if (!is_object($this->soapcli)) {
                return "Error in constructor compilatio() ".$this->soapcli;
            }
            $param = [$this->key, $compiHash];
            $this->soapcli->__call('deleteDocument', $param);
        } catch (SoapFault $fault) {
            return "Erreur  deldoc()".$fault->faultcode." ".$fault->faultstring;
        }
    }

    /**
     * Method for start the analysis for a document.
     *
     * @param $compiHash
     *
     * @return string
     */
    public function startAnalyse($compiHash)
    {
        try {
            if (!is_object($this->soapcli)) {
                return "Error in constructor compilatio() ".$this->soapcli;
            }
            $param = [$this->key, $compiHash];
            $this->soapcli->__call('startDocumentAnalyse', $param);
        } catch (SoapFault $fault) {
            return "Erreur  startAnalyse()".$fault->faultcode." ".$fault->faultstring;
        }
    }

    /**
     * Method for recover the account's quota.
     *
     * @return string
     */
    public function getQuotas()
    {
        try {
            if (!is_object($this->soapcli)) {
                return "Error in constructor compilatio() ".$this->soapcli;
            }
            $param = [$this->key];

            return $this->soapcli->__call('getAccountQuotas', $param);
        } catch (SoapFault $fault) {
            return "Erreur  getQuotas()".$fault->faultcode." ".$fault->faultstring;
        }
    }

    /**
     * Method for identify a file extension and the possibility that the document can be managed by Compilatio.
     *
     * @param $filename
     *
     * @return bool
     */
    public static function verifiFileType($filename)
    {
        $types = ['doc', 'docx', 'rtf', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'pdf', 'txt', 'htm', 'html'];
        $extension = substr($filename, strrpos($filename, '.') + 1);
        $extension = strtolower($extension);

        return in_array($extension, $types);
    }

    /**
     * Fonction affichage de la barre de progression d'analyse version 3.1.
     *
     * @param string $status From the document
     * @param int    $pour
     * @param array  $text   Array includes the extract from the text
     *
     * @return string
     */
    public static function getProgressionAnalyseDocv31($status, $pour = 0, $text = [])
    {
        $loading = Display::returnFontAwesomeIcon('spinner', null, true, 'fa-spin');
        $loading .= '&nbsp;';

        switch ($status) {
            case 'ANALYSE_IN_QUEUE':
                $content = $loading.$text['analysisinqueue'];
                break;
            case 'ANALYSE_PROCESSING':
                $content = $loading.$text['analysisinfinalization'];
                break;
            default:
                $content = Display::bar_progress($pour, true);
                break;
        }

        return $content;
    }

    /**
     * Method for display the PomprseuilmankBar (% de plagiat).
     *
     * @param $index
     * @param $weakThreshold
     * @param $highThreshold
     *
     * @return string
     */
    public static function getPomprankBarv31(
        $index,
        $weakThreshold,
        $highThreshold
    ) {
        $index = round($index);
        $class = 'error';
        if ($index < $weakThreshold) {
            $class = 'success';
        } else {
            if ($index >= $weakThreshold && $index < $highThreshold) {
                $class = 'warning';
            }
        }

        return Display::bar_progress($index, true, null, $class);
    }

    /**
     * Method for validation of hash.
     *
     * @param string $hash
     *
     * @return bool
     */
    public static function isMd5($hash)
    {
        return preg_match('`^[a-f0-9]{32}$`', $hash) || preg_match('`^[a-f0-9]{40}$`', $hash);
    }

    /**
     * function for delete a document of the compilatio table if plagiarismTool is Compilatio.
     *
     * @param int $courseId
     * @param int $itemId
     *
     * @return bool
     */
    public static function plagiarismDeleteDoc($courseId, $itemId)
    {
        if (api_get_configuration_value('allow_compilatio_tool') === false) {
            return false;
        }

        $table = Database::get_course_table(TABLE_PLAGIARISM);
        $params = [$courseId, $itemId];
        Database::delete($table, ['c_id = ? AND document_id = ?' => $params]);

        return true;
    }

    /**
     * @param int $courseId
     * @param int $documentId
     * @param int $compilatioId
     */
    public function saveDocument($courseId, $documentId, $compilatioId)
    {
        $documentId = (int) $documentId;
        $courseId = (int) $courseId;

        $table = Database::get_course_table(TABLE_PLAGIARISM);
        $params = [
            'c_id' => $courseId,
            'document_id' => $documentId,
            'compilatio_id' => $compilatioId,
        ];
        Database::insert($table, $params);
    }

    /**
     * @param int $documentId
     * @param int $courseId
     *
     * @return string md5 value
     */
    public function getCompilatioId($documentId, $courseId)
    {
        $documentId = (int) $documentId;
        $courseId = (int) $courseId;

        $table = Database::get_course_table(TABLE_PLAGIARISM);
        $sql = "SELECT compilatio_id FROM $table
                WHERE document_id = $documentId AND c_id= $courseId";
        $result = Database::query($sql);
        $result = Database::fetch_object($result);

        if ($result) {
            return (string) $result->compilatio_id;
        }

        return 0;
    }

    /**
     * @param int $workId
     *
     * @return string
     */
    public function giveWorkIdState($workId)
    {
        $courseId = api_get_course_int_id();
        $compilatioId = $this->getCompilatioId($workId, $courseId);

        $actionCompilatio = '';
        $status = '';
        if (!empty($compilatioId)) {
            if (self::isMd5($compilatioId)) {
                // if compilatio_id is a hash md5, we call the function of the compilatio's
                // webservice who return the document's status
                $soapRes = $this->getDoc($compilatioId);
                if (isset($soapRes->documentStatus)) {
                    $status = $soapRes->documentStatus->status;
                }
            } else {
                // if the compilatio's hash is not a valide hash md5,
                // we return à specific status (cf : IsInCompilatio() )
                $status = 'NOT_IN_COMPILATIO';
                $actionCompilatio = get_lang('CompilatioDocumentTextNotImage').'<br/>'.
                    get_lang('CompilatioDocumentNotCorrupt');
            }

            switch ($status) {
                case 'ANALYSE_COMPLETE':
                    $urlRapport = $this->getReportUrl($compilatioId);
                    $actionCompilatio .= self::getPomprankBarv31(
                            $soapRes->documentStatus->indice,
                            10,
                            35
                        )
                        .Display::url(
                            get_lang('CompilatioAnalysis'),
                            $urlRapport,
                            ['class' => 'btn btn-primary btn-xs', 'target' => '_blank']
                        );
                    break;
                case 'ANALYSE_PROCESSING':
                    $actionCompilatio .= "<div style='font-weight:bold;text-align:left'>"
                        .get_lang('CompilatioAnalysisInProgress')
                        ."</div>";
                    $actionCompilatio .= "<div style='font-size:80%;font-style:italic;margin-bottom:5px;'>"
                        .get_lang('CompilatioAnalysisPercentage')
                        ."</div>";
                    $text = [];
                    $text['analysisinqueue'] = get_lang('CompilatioWaitingAnalysis');
                    $text['analysisinfinalization'] = get_lang('CompilatioAnalysisEnding');
                    $text['refresh'] = get_lang('Refresh');
                    $actionCompilatio .= self::getProgressionAnalyseDocv31(
                        $status,
                        $soapRes->documentStatus->progression,
                        $text
                    );
                    break;
                case 'ANALYSE_IN_QUEUE':
                    $loading = Display::returnFontAwesomeIcon('spinner', null, true, 'fa-spin');
                    $actionCompilatio .= $loading.'&nbsp;'.get_lang('CompilatioAwaitingAnalysis');
                    break;
                case 'BAD_FILETYPE':
                    $actionCompilatio .= get_lang('FileFormatNotSupported')
                        .'<br/>'
                        .get_lang('CompilatioProtectedPdfVerification');
                    break;
                case 'BAD_FILESIZE':
                    $actionCompilatio .= get_lang('UplFileTooBig');
                    break;
            }
        }

        return $workId.'|'.$actionCompilatio.'|'.$status.'|';
    }
}
