<?php

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

function pageGenerationProcess($iPR, $pid, $courseSys, $extra, $extraCss, $extraJS, $index, $idPgTop, $base_html, $base_css, $prevIdent, $nextIdent, $behavior, $strActivityId, $projLang, $projOptions, $alone, $titlePage): void
{
    $fileSystem = Container::getAssetRepository()->getFileSystem();

    $courseSysPage0 = $courseSys."/teachdoc-$pid.html";

    if (true == $alone) {
        $titlePage = strtolower($titlePage);
        $titlePage = str_replace(' ', '-', $titlePage);
        $titlePage = str_replace(':', '-', $titlePage);
        $titlePage = str_replace('?', '-', $titlePage);
        $courseSysPage0 = $courseSys."/alone-$titlePage.html";
    }
    echo '<span style="color:blue;" >'.$courseSysPage0.'</span></br>';

    if ($pid == $idPgTop) {
        $courseSysPageIndex = $courseSys.'/index.html';
        $dataIndex = $fileSystem->read($courseSysPageIndex);
        $dataIndex = str_replace('{start}', (string) $pid, $dataIndex);
        $dataIndex = str_replace('{activityid}', $strActivityId, $dataIndex);
        $dataIndex = str_replace('{projLang}', $projLang, $dataIndex);
        $dataIndex = str_replace('{projOptions}', $projOptions, $dataIndex);

        $fileSystem->write($courseSysPageIndex, $dataIndex);
    }

    if (false == $alone) {
        if (-1 == $nextIdent) {
            $extra = str_replace('act-next', ' style="opacity:0.2;" ', $extra);
        } else {
            $actn = ' onClick="ctrlpl(basePages['.($index + 2).'],'.$behavior.','.($index + 2).',behavPages['.($index + 2).']);" ';
            $extra = str_replace('act-next', $actn, $extra);
        }
        if (-1 == $prevIdent) {
            $extra = str_replace('act-prev', ' style="opacity:0.2;" ', $extra);
        } else {
            $extra = str_replace('act-prev', ' onClick="ctrlpl(basePages['.$index.'],0,'.$index.',behavPages['.$index.']);" ', $extra);
        }
    }

    $extra = str_replace('act-bahavior', (string) $behavior, $extra);

    $base_html = str_replace('img/qcm/matgreen1.png', 'img/qcm/matgreen0r.png', $base_html);
    $base_html = str_replace('img/qcm/catgreen1.png', 'img/qcm/catgreen0r.png', $base_html);

    $base_html = str_replace('onmousedown="parent.displayEditButon(this);"', ' ', $base_html);
    $base_html = str_replace('$pluginfx-obj$', '', $base_html);

    $finalTop = '<!doctype html><html><head>';
    $finalTop .= '<title>'.$titlePage.'</title>';
    $finalTop .= '<meta charset="utf-8">';

    if (false == $alone) {
        $finalTop .= '<script>';
        $finalTop .= 'var additional_params = "";';
        $finalTop .= 'var logs_params = "";';
        if (oel_ctr_options('ALL')) {
            $finalTop .= 'var sendLogsToTableOpt = 1;';
        } else {
            $finalTop .= 'var sendLogsToTableOpt = 0;';
        }
        $finalTop .= 'var projLang = "'.$projLang.'";';
        $finalTop .= 'var projOptions = "'.$projOptions.'";';
        $finalTop .= 'var localactivityid = "'.$strActivityId.'";';
        $finalTop .= '</script>';
        // $finalTop .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $finalTop .= '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>';
        $finalTop .= '<link href="css/scorm.css?v=4" rel="stylesheet" type="text/css" />';
    }

    $finalTop .= '</head>';

    $finalTop .= '<body style="background-color:#D8D8D8;" >';
    $finalhtml = getSrcForSave($base_html);
    $finalhtml = str_replace('dhcondiM', 'displayhideCondiM', $finalhtml);

    if (false == $alone) {
        $finalFooter = '<style>body,html {height: 100%;';
        $finalFooter .= 'margin: 0;}'.$extraCss.$base_css;
        $finalFooter .= '.cell{border:dashed 0px #A9CCE3;}';
        $finalFooter .= '.displayhideCondiMB,.displayhideCondiMC,.displayhideCondiME{display:none;}';
        $finalFooter .= '</style>';
    } else {
        $finalFooter = '';
    }

    $finalFooter .= '</body></html>';

    $base_html = $finalTop.$finalhtml.$finalFooter;

    $extraH = str_replace('subMenuMini'.$index, 'activeli', $extra);
    $base_html = str_replace('</head>', '</head>'.$extraH, $base_html);

    $extraJS = '<script>var pageBindex='.(int) ($index + 1).';</script>'.$extraJS;

    $extraJS .= '<a class="btninfos" onClick="showScoParamsWindow()" ></a>';

    if (false != strpos($base_html, 'txtmathjax')) {
        $extraJS .= '<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>';
        $extraJS .= '<script id="MathJax-script" async ';
        $extraJS .= 'src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>';
    }

    $base_html = str_replace('</body>', $extraJS.'</body>', $base_html);

    $fileSystem->write($courseSysPage0, $base_html);
}
