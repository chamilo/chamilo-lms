<?php
/* For licensing terms, see /license.txt */

/**
 * Build the comunication with the SOAP server Compilatio.net
 * call severals methods for the file management in Compilatio.net
 *
 * Date: 26/05/16
 * @version:1.0
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
     *
     */
    public function __construct()
    {
        if (empty(api_get_configuration_value('allow_compilatio_tool')) ||
            empty(api_get_configuration_value('compilatio_tool'))
        ) {
            throw new Exception('Compilation not available');
        }

        $settings = api_get_configuration_value('compilatio_tool');

        $key = $this->key = $settings['key'];
        $urlsoap = $settings['soap_url'];
        $proxyHost = $this->proxyHost = $settings['proxy_host'];
        $proxyPort = $this->proxyPort = $settings['proxy_port'];
        $this->transportMode = $settings['transport_mode'];
        $this->maxFileSize = $settings['max_filesize'];
        $this->wgetUri = $settings['wget_uri'];
        $this->wgetLogin = $settings['wget_login'];
        $this->wgetPassword = $settings['wget_password'];

        try {
            if (!empty($key)) {
                $this->key = $key;
                if (!empty($urlsoap)) {
                    if (!empty($proxyHost)) {
                        $param = array(
                            'trace' => false,
                            'soap_version' => SOAP_1_2,
                            'exceptions' => true,
                            'proxy_host' => '"'.$proxyHost.'"',
                            'proxy_port' => $proxyPort,
                        );
                    } else {
                        $param = array(
                            'trace' => false,
                            'soap_version' => SOAP_1_2,
                            'exceptions' => true,
                        );
                    }
                    $this->soapcli = new SoapClient($urlsoap, $param);
                } else {
                    $this->soapcli = 'WS urlsoap not available';
                }
            } else {
                $this->soapcli = 'API key not available';
            }
        } catch (SoapFault $fault) {
            $this->soapcli = "Error constructor compilatio ".$fault->faultcode." ".$fault->faultstring;
        } catch (Exception $e) {
            $this->soapcli = "Error constructor compilatio with urlsoap".$urlsoap;
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
     * Method for the file load
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
                return ("Error in constructor compilatio() ".$this->soapcli);
            }
            $idDocument = $this->soapcli->__call(
                'addDocumentBase64',
                array(
                    $this->key,
                    utf8_encode(urlencode($title)),
                    utf8_encode(urlencode($description)),
                    utf8_encode(urlencode($filename)),
                    utf8_encode($mimeType),
                    base64_encode($content),
                )
            );

            return $idDocument;
        } catch (SoapFault $fault) {
            return ("Erreur sendDoc()".$fault->faultcode." ".$fault->faultstring);
        }
    }

    /**
     * Method for recover a document's information
     *
     * @param $compiHash
     *
     * @return string
     */
    public function getDoc($compiHash)
    {
        try {
            if (!is_object($this->soapcli)) {
                return ("Error in constructor compilatio() ".$this->soapcli);
            }
            $param = array($this->key, $compiHash);
            $idDocument = $this->soapcli->__call('getDocument', $param);

            return $idDocument;
        } catch (SoapFault $fault) {
            return ("Erreur getDoc()".$fault->faultcode." ".$fault->faultstring);
        }
    }

    /**
     * method for recover an url document's report
     *
     * @param $compiHash
     *
     * @return string
     */
    public function getReportUrl($compiHash)
    {
        try {
            if (!is_object($this->soapcli)) {
                return ("Error in constructor compilatio() ".$this->soapcli);
            }
            $param = array($this->key, $compiHash);
            $idDocument = $this->soapcli->__call('getDocumentReportUrl', $param);

            return $idDocument;
        } catch (SoapFault $fault) {
            return ("Erreur  getReportUrl()".$fault->faultcode." ".$fault->faultstring);
        }
    }

    /**
     *  Method for deleting a Compialtio's account document
     *
     * @param $compiHash
     *
     * @return string
     */
    public function deldoc($compiHash)
    {
        try {
            if (!is_object($this->soapcli)) {
                return ("Error in constructor compilatio() ".$this->soapcli);
            }
            $param = array($this->key, $compiHash);
            $this->soapcli->__call('deleteDocument', $param);
        } catch (SoapFault $fault) {
            return ("Erreur  deldoc()".$fault->faultcode." ".$fault->faultstring);
        }
    }

    /**
     * Method for start the analysis for a document
     *
     * @param $compiHash
     *
     * @return string
     */
    public function startAnalyse($compiHash)
    {
        try {
            if (!is_object($this->soapcli)) {
                return ("Error in constructor compilatio() ".$this->soapcli);
            }
            $param = array($this->key, $compiHash);
            $this->soapcli->__call('startDocumentAnalyse', $param);
        } catch (SoapFault $fault) {
            return ("Erreur  startAnalyse()".$fault->faultcode." ".$fault->faultstring);
        }
    }

    /**
     * Method for recover the account's quota
     * @return string
     */
    public function getQuotas()
    {
        try {
            if (!is_object($this->soapcli)) {
                return ("Error in constructor compilatio() ".$this->soapcli);
            }
            $param = array($this->key);
            $resultat = $this->soapcli->__call('getAccountQuotas', $param);

            return $resultat;
        } catch (SoapFault $fault) {
            return ("Erreur  getQuotas()".$fault->faultcode." ".$fault->faultstring);
        }
    }

    /**
     * Method for identify a file extension and the possibility that the document can be managed by Compilatio
     *
     * @param $filename
     *
     * @return bool
     */
    public static function verifiFileType($filename)
    {
        $types = array('doc', 'docx', 'rtf', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'pdf', 'txt', 'htm', 'html');
        $extension = substr($filename, strrpos($filename, '.') + 1);
        $extension = strtolower($extension);

        return in_array($extension, $types);
    }

    /**
     * Fonction  affichage de la barre de progression d'analyse version 3.1
     *
     * @param string $status From the document
     * @param $pour
     * @param string $imagesPath
     * @param array $text   Array includes the extract from the text
     *
     * @return unknown_type
     */
    public static function getProgressionAnalyseDocv31($status, $pour = 0, $imagesPath = '', $text = [])
    {
        $refreshReturn = "<a href='javascript:window.location.reload(false);'><img src='"
            .$imagesPath
            ."ajax-loader_green.gif' title='"
            .$text['refresh']
            ."' alt='"
            .$text['refresh']
            ."'/></a> ";
        $startReturn = "<table cellpadding='0' cellspacing='0' style='border:0;margin:0;padding:0;'><tr>";
        $startReturn .= "<td width='25' style='border:0;margin:0;padding:0;'>&nbsp;</td>";
        $startReturn .= "<td valign='middle' align='right' style='border:0;margin:0;padding-right:10px;'>"
            .$refreshReturn
            ."</td>";
        $startReturn .= "<td style='border:0;margin:0;padding:0;'>";
        $startReturnLittleWidth = "<table cellpadding='0' cellspacing='0' style='border:0;margin:0;padding:0;'><tr>";
        $startReturnLittleWidth .= "<td width='25' valign='middle' align='right' style='border:0;margin:0;padding:0;'>"
            .$refreshReturn
            ."</td>";
        $finretour = "</td></tr></table>";

        if ($status == 'ANALYSE_IN_QUEUE') {
            return $startReturn."<span style='font-size:11px'>".$text['analysisinqueue']."</span>".$finretour;
        }
        if ($status == 'ANALYSE_PROCESSING') {
            if ($pour == 100) {
                return $startReturn
                    ."<span style='font-size:11px'>"
                    .$text['analysisinfinalization']
                    ."</span>"
                    .$finretour;
            } else {
                return $startReturnLittleWidth
                    ."<td align=\"right\" style=\"border:0;margin:0;padding-right:10px;\">"
                    .$pour
                    ."%</td><td style=\"border:0;margin:0;padding:0;\"><div style=\"background"
                    .":transparent url("
                    .$imagesPath
                    ."mini-jauge_fond.png) no-repeat scroll 0;height:12px;padding:0 0 0 2px;width:55px;\"><div style=\""
                    ."background:transparent url("
                    .$imagesPath
                    ."mini-jauge_gris.png) no-repeat scroll 0;height:12px;width:"
                    .$pour / 2
                    ."px;\"></div></div>"
                    .$finretour;
            }
        }
    }

    /**
     * Method for display the PomprseuilmankBar (% de plagiat)
     *
     * @param $percentagePumping
     * @param $weakThreshold
     * @param $highThreshold
     * @param $imagesPath
     * @param $text : array  includes the extract from the text
     *
     * @return unknown_type
     */
    public static function getPomprankBarv31($pourcentagePompage, $weakThreshold, $highThreshold, $chemin_images = '', $texte = '')
    {
        $pourcentagePompage = round($pourcentagePompage);
        $pour = round((50 * $pourcentagePompage) / 100);
        $return = '';
        if ($pourcentagePompage < $weakThreshold) {
            $couleur = "vert";
        } else {
            if ($pourcentagePompage >= $weakThreshold && $pourcentagePompage < $highThreshold) {
                $couleur = "orange";
            } else {
                $couleur = "rouge";
            }
        }
        $return .= "<div style='float:left;margin-right:2px;'><img src='"
            .$chemin_images."mini-drapeau_$couleur.png' title='"
            .$texte['result']
            ."' alt='faible' width='15' height='15' /></div>";
        $return .= "<div style='float:left; margin-right:5px;width:45px;text-align:right;'>"
            .$pourcentagePompage
            ." %</div>";
        $return .= "<div style='float:left;background:transparent url("
            .$chemin_images
            ."mini-jauge_fond.png) no-repeat scroll 0;height:12px;margin-top:5px;padding:0 0 0 2px;width:55px;'>";
        $return .= "<div style='float:left;background:transparent url("
            .$chemin_images
            ."mini-jauge_"
            .$couleur
            .".png) no-repeat scroll 0;height:12px;width:"
            .$pour
            ."px'></div></div>";

        return $return;
    }

    /**
     * Method for validation of hash
     * @param $hash
     * @return bool
     *
     */
    public static function isMd5($hash)
    {
        return preg_match('`^[a-f0-9]{32}$`', $hash);
    }

    /*
     * Method for identify Internet media type
     * @param $filename
     */
    public static function typeMime($filename)
    {
        if (preg_match("@Opera(/| )([0-9].[0-9]{1,2})@", $_SERVER['HTTP_USER_AGENT'], $resultats)) {
            $navigateur = "Opera";
        } elseif (preg_match("@MSIE ([0-9].[0-9]{1,2})@", $_SERVER['HTTP_USER_AGENT'], $resultats)) {
            $navigateur = "Internet Explorer";
        } else {
            $navigateur = "Mozilla";
            $mime = parse_ini_file("mime.ini");
            $extension = substr($filename, strrpos($filename, ".") + 1);
        }
        if (array_key_exists($extension, $mime)) {
            $type = $mime[$extension];
        } else {
            $type = ($navigateur != "Mozilla") ? 'application/octetstream' : 'application/octet-stream';
        }

        return $type;
    }

    /**
     * function for delete a document of the compilatio table if plagiarismTool is Compilatio
     * @param int $courseId
     * @param int $itemId
     */
    public static function plagiarismDeleteDoc($courseId, $itemId)
    {
        if (api_get_configuration_value('allow_compilatio_tool')) {
            return false;
        }

        $table = Database:: get_course_table(TABLE_PLAGIARISM);
        $params = [$courseId, $itemId];
        Database::delete($table, ['c_id = ? AND document_id = ?' => $params]);
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
        $compilatioId = (int) $compilatioId;

        $table = Database::get_course_table(TABLE_PLAGIARISM);
        $params = [
            'c_id' => $courseId,
            'document_id' => $documentId,
            'compilatio_id' => $compilatioId,
        ];
        Database::insert($table, $params);
    }

    /**
     * @param int $itemId
     * @param int $courseId
     *
     * @return int
     */
    public function getCompilatioId($itemId, $courseId)
    {
        $itemId = (int) $itemId;
        $courseId = (int) $courseId;

        $table = Database::get_course_table(TABLE_PLAGIARISM);
        $sql = "SELECT compilatio_id FROM $table 
                WHERE document_id = $itemId AND c_id= $courseId";
        $compiSqlResult = Database::query($sql);
        $result = Database::fetch_object($compiSqlResult);

        if ($result) {
            return (int) $result->compilatio_id;
        }

        return 0;
    }

    /**
     * @param $workId
     *
     * @return string
     */
    public function giveWorkIdState($workId)
    {
        $text = '';
        $compilatioImgFolder = api_get_path(WEB_CODE_PATH).'plagiarism/compilatio/img/';
        $courseId = api_get_course_int_id();
        $compilatioId = $this->getCompilatioId($workId, $courseId);

        $actionCompilatio = '';
        if (!empty($compilatioId)) {
            if (self::isMd5($compilatioId)) {
                // if compilatio_id is a hash md5, we call the function of the compilatio's webservice who return the document's status
                $soapRes = $this->getDoc($compilatioId);
                $status = '';
                if (isset($soapRes->documentStatus)) {
                    $status = $soapRes->documentStatus->status;
                }
            } else {
                // if the compilatio's hash is not a valide hash md5, we return à specific status (cf : IsInCompilatio() )
                $status = 'NOT_IN_COMPILATIO';
                $actionCompilatio = "<div style='font-style:italic'>"
                    .get_lang('compilatioDocumentTextNotImage')
                    ."<br/>"
                    .get_lang('compilatioDocumentNotCorrupt')
                    ."</div>";
            }

            if ($status === 'ANALYSE_COMPLETE') {
                $urlRapport = $this->getReportUrl($compilatioId);
                $actionCompilatio .= self::getPomprankBarv31(
                        $soapRes->documentStatus->indice,
                        10,
                        35,
                        $compilatioImgFolder,
                        $text
                    )
                    ."<br/><a href='"
                    .$urlRapport
                    ."' target='_blank'>"
                    .get_lang('compilatioSeeReport')
                    ."</a>";
            } elseif ($status === 'ANALYSE_PROCESSING') {
                $actionCompilatio .= "<div style='font-weight:bold;text-align:left'>"
                    .get_lang('compilatioAnalysisInProgress')
                    ."</div>";
                $actionCompilatio .= "<div style='font-size:80%;font-style:italic;margin-bottom:5px;'>"
                    .get_lang('compilatioAnalysisPercentage')
                    ."</div>";
                $text['analysisinqueue'] = get_lang('compilatioWaitingAnalysis');
                $text['analysisinfinalization'] = get_lang('compilatioAnalysisEnding');
                $text['refresh'] = get_lang('Refresh');
                $actionCompilatio .= self::getProgressionAnalyseDocv31(
                    $status,
                    $soapRes->documentStatus->progression,
                    $compilatioImgFolder,
                    $text
                );
            } elseif ($status == 'ANALYSE_IN_QUEUE') {
                $actionCompilatio .= "<img src='"
                    .$compilatioImgFolder
                    ."/ajax-loader2.gif' style='margin-right:10px;' />"
                    .get_lang('compilatioAwaitingAnalysis');
            } elseif ($status == 'BAD_FILETYPE') {
                $actionCompilatio .= "<div style='font-style:italic'>"
                    .get_lang('compilatioFileisnotsupported')
                    ."<br/>"
                    .get_lang('compilatioProtectedPdfVerification')
                    ."</div>";
            } elseif ($status == 'BAD_FILESIZE') {
                $actionCompilatio .= "<div style='font-style:italic'>"
                    .get_lang('compilatioTooHeavyDocument')
                    ."</div>";
            } elseif ($status != 'NOT_IN_COMPILATIO') {
                $actionCompilatio .= "<div style='font-style:italic'>"
                    .get_lang('compilatioMomentarilyUnavailableResult')
                    ." : [ "
                    .$status
                    ."].</div>";
            }
        }

        $result = $workId.'|'.$actionCompilatio.'|'.$status.'|';

        return $result;
    }

}
