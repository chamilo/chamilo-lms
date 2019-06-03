<?php
    require ('../../inc/global.inc.php');
    include('config.php');
    include('compilatio.class.php');

    if (isset($_GET['workid'])) {
        $compTable = Database::get_course_table(TABLE_PLAGIARISM);
        $workIdList = $_GET['workid'];	// list of workid separate by the :
        $result = "";
//        $tabWorkId =  split("a", $workIdList);
        $tabWorkId =  explode("a", $workIdList);
        for ($i=0; $i < count($tabWorkId); $i++) {
            if (is_numeric($tabWorkId[$i])) {
                $result .= giveWorkIdState($tabWorkId[$i]);
            }
        }
        echo $result;
    }
    /**
     * @param $workId
     * @return string
     */
    function giveWorkIdState($workId) {
        global $compilatioParameter;
        global $compTable;
        $compilatio = new compilatio(
            $compilatioParameter['key'],
            $compilatioParameter['$urlsoap'],
            $compilatioParameter['proxy_host'],
            $compilatioParameter['proxy_port']
        );
        $text = "";
        $result = "";
        $compilatioImgFolder = api_get_path(WEB_CODE_PATH)."plagiarism/compilatio/img/";
        $compilatioWebFolder = api_get_path(WEB_CODE_PATH)."plagiarism/compilatio/";
        $courseId = api_get_course_int_id();
        $compilatioQuery = "SELECT compilatio_id 
           FROM $compTable 
           where id_doc = $workId 
           AND c_id = $courseId";
        $compiSqlResult = Database::query($compilatioQuery);
        $compi = Database::fetch_object($compiSqlResult);
        if (isset($compi->compilatio_id)) {
            $actionCompilatio = "";
            if(isMd5($compi->compilatio_id)) {
                // if compilatio_id is a hash md5, we call the function of the compilatio's webservice who return the document's status

                $soapRes = $compilatio->getDoc($compi->compilatio_id);
                $status = '';
                if(isset($soapRes->documentStatus)) {
                    $status = $soapRes->documentStatus->status;
                }
            } else {
                // if the compilatio's hash is not a valide hash md5, we return à specific status (cf : IsInCompilatio() )
                $status="NOT_IN_COMPILATIO";
                $actionCompilatio = "<div style='font-style:italic'>"
                    . get_lang('compilatioDocumentTextNotImage')
                    . "<br/>"
                    . get_lang('compilatioDocumentNotCorrupt')
                    . "</div>";
            }

            if ($status == "ANALYSE_COMPLETE") {
                $urlRapport = $compilatio->getReportUrl($compi->compilatio_id);
                $actionCompilatio .= getPomprankBarv31(
                        $soapRes->documentStatus->indice,
                        10 ,
                        35,
                        $compilatioImgFolder,
                        $text
                    )
                    . "<br/><a href='"
                    . $urlRapport
                    . "' target='_blank'>"
                    . get_lang('compilatioSeeReport')
                    . "</a>";
            } elseif ($status == "ANALYSE_PROCESSING") {
                $actionCompilatio .= "<div style='font-weight:bold;text-align:left'>"
                    . get_lang('compilatioAnalysisInProgress')
                    . "</div>";
                $actionCompilatio .= "<div style='font-size:80%;font-style:italic;margin-bottom:5px;'>"
                    . get_lang('compilatioAnalysisPercentage')
                    . "</div>";
                $text['analysisinqueue'] = get_lang('compilatioWaitingAnalysis');
                $text['analysisinfinalization'] = get_lang('compilatioAnalysisEnding');
                $text['refresh'] = get_lang('Refresh');
                $actionCompilatio .= getProgressionAnalyseDocv31(
                    $status,
                    $soapRes->documentStatus->progression,
                    $compilatioImgFolder,
                    $text
                );
            }
            elseif ($status == "ANALYSE_IN_QUEUE") {
                $actionCompilatio.="<img src='"
                    . $compilatioImgFolder
                    . "/ajax-loader2.gif' style='margin-right:10px;' />"
                    . get_lang('compilatioAwaitingAnalysis');
            } elseif ($status == "BAD_FILETYPE") {
                $actionCompilatio.= "<div style='font-style:italic'>"
                    . get_lang('compilatioFileisnotsupported')
                    . "<br/>"
                    . get_lang('compilatioProtectedPdfVerification')
                    . "</div>";
            } elseif ($status == "BAD_FILESIZE") {
                $actionCompilatio.=  "<div style='font-style:italic'>"
                    . get_lang('compilatioTooHeavyDocument')
                    . "</div>";
            } elseif ($status != "NOT_IN_COMPILATIO") {
                $actionCompilatio.= "<div style='font-style:italic'>"
                    . get_lang('compilatioMomentarilyUnavailableResult')
                    . " : [ "
                    . $status
                    . "].</div>";
            }
        }
        $result = $workId . "|" . $actionCompilatio . "|" . $status . "|";

        return $result;
    }
?>
