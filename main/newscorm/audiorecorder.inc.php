<?php
/* For licensing terms, see /license.txt */

/**
 * Created on 27.09.2006
 * Include the lazlo file necessary to use the audiorecorder
 * @author Sebastian Wagner <seba.wagner@gmail.com>
 * @author Eric Marguin <e.marguin@elixir-interactive.com>
 * @author Arnaud Ligot <arnaud@cblue.be>
 * @package chamilo.learnpath
 */

global $_configuration;
$web_path = api_get_path(WEB_CODE_PATH);
$getid3_path = api_get_path(LIBRARY_PATH);

require_once $getid3_path.'getid3/getid3.php';

function getFLVDuration($flv_path) {
    $getid3 = new getID3;
    $getid3->encoding = 'UTF-8';
    try {
        $getid3->Analyze($flv_path);
        return $getid3->info['playtime_seconds'];
    } catch (Exception $e) {
        return 0;
    }
}

if ($audio_recorder_studentview == 'false') {
    $width = 295;
    $height= 90;
    $player = 'recorder2.swf';
    $server = (api_get_setting('service_visio', 'visio_use_rtmpt') == 'true' ? 'rtmpt://' : 'rtmp://').api_get_setting('service_visio', 'visio_host').':'.(api_get_setting('service_visio', 'visio_use_rtmpt') == 'true' ? '80' : api_get_setting('service_visio', 'visio_port')).'/recorder';
    $post_uri = urlencode($web_path.'conference/audiopost.php?course_code='.api_get_course_id().'&user_id='.api_get_user_id().'&checker='.md5(api_get_course_id().api_get_user_id().gmdate('Ymd').$_configuration['security_key']));
    $filename = 'lpi'.$audio_recorder_item_id.'-'.gmdate('YmdHis').api_get_user_id().'.flv'; // Using -z- as fields splitter.
    $path_to_lzx = $web_path.'conference/'.$player.'?server='.urlencode($server).'&postURI='.$post_uri.'&filename='.$filename;
} else {
    $width = 295;
    $height = 24;
    $player = 'player2.swf';
    $cp = api_get_course_path();
    $docs = Database::get_course_table(TABLE_DOCUMENT);
    $select = "SELECT * FROM $docs " .
            " WHERE path like BINARY '/audio/lpi".Database::escape_string($audio_recorder_item_id)."-%' AND filetype='file' " .
            " ORDER BY path DESC";
    $res = Database::query($select);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_array($res);
        //$filepath = api_get_path(WEB_COURSE_PATH).$cp.'/document'.$row['path'];
        $duration = getFLVDuration(api_get_path(SYS_COURSE_PATH).$cp.'/document'.$row['path']);
        $filepath = api_get_path(WEB_CODE_PATH).'document/download.php?'.api_get_cidreq().'&doc_url='.$row['path'];
        $path_to_lzx = $web_path.'conference/'.$player.'?uri='.urlencode($filepath).'&autostart=true&duration='.$duration;
    }
}

if (!empty($path_to_lzx)) {
    $recorder_content = sprintf("<object type=\"application/x-shockwave-flash\" data=\"%s\" ".
            "width='$width' height='$height'>".
             "<param name=\"movie\" value=\"%s\">".
         "<param name=\"quality\" value=\"high\">".
         "<param name=\"scale\" value=\"noscale\">".
         "<param name=\"salign\" value=\"LT\">".
         "<param name=\"menu\" value=\"false\"></object>", $path_to_lzx, $path_to_lzx);
    if ($audio_recorder_studentview == 'false') {
        echo '<script type="text/javascript">

        function show_audiorecorder()
        {
            document.getElementById("audiorecorder_frame").innerHTML = "'.addslashes($recorder_content).'";
            document.getElementById("show_audiorecorder_div").style.display="none";
        }

        </script>
        ';
        // Commented the audio for the version stable.
        //echo '<div id="show_audiorecorder_div"><a style="cursor:pointer" onclick="show_audiorecorder()">'.get_lang('ShowAudioRecorder').'</a></div>';
        //echo '<div id="audiorecorder_frame"></div>';
    } else {
        echo $recorder_content;
    }
}
