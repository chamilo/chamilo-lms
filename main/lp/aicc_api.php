<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * API event handler functions for AICC / CMIv4 in API communication mode.
 *
 * @author   Denes Nagy <darkden@freemail.hu>
 * @author   Yannick Warnier <ywarnier@beeznest.org>
 *
 * @version  v 1.0
 *
 * @package  chamilo.learnpath
 *
 * @license    GNU/GPL
 */

/**
 * This script is divided into three sections.
 * The first section (below) is the initialisation part.
 * The second section is the AICC object part
 * The third section defines the event handlers for Chamilo's internal messaging
 * and frames refresh.
 *
 * This script implements the API messaging for AICC. The HACP messaging is
 * made by another set of scripts.
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

// Load common libraries using a compatibility script to bridge between 1.6 and 1.8.
require_once __DIR__.'/../inc/global.inc.php';

// Is this needed? This is probabaly done in the header file.
$file = Session::read('file');
/** @var learnpath $oLP */
$oLP = UnserializeApi::unserialize('lp', Session::read('lpobject'));
$oItem = $oLP->items[$oLP->current];
if (!is_object($oItem)) {
    error_log('New LP - scorm_api - Could not load oItem item', 0);
    exit;
}
$autocomplete_when_80pct = 0;

/* JavaScript Functions */

?>var scorm_logs=<?php echo empty($oLP->scorm_debug) ? '0' : '3'; ?>; //debug log level for SCORM. 0 = none, 1=light, 2=a lot, 3=all - displays logs in log frame
var lms_logs=0; //debug log level for LMS actions. 0=none, 1=light, 2=a lot, 3=all
//logit_lms('scormfunctions.php included',0);

function APIobject() {
  this.LMSInitialize=LMSInitialize;
  this.LMSGetValue=LMSGetValue;
  this.LMSSetValue=LMSSetValue;
  this.LMSCommit=LMSCommit;
  this.LMSFinish=LMSFinish;
  this.LMSGetLastError=LMSGetLastError;
  this.LMSGetErrorString=LMSGetErrorString;
  this.LMSGetDiagnostic=LMSGetDiagnostic;
}

// It is not sure that the scos use the above declarations.

API = new APIobject(); //for scorm 1.2

var G_NoError = 0;
var G_GeneralException = 101;
var G_ServerBusy = 102;
var G_InvalidArgumentError = 201;
var G_ElementCannotHaveChildren = 202;
var G_ElementIsNotAnArray = 203;
var G_NotInitialized = 301;
var G_NotImplementedError = 401;
var G_InvalidSetValue = 402;
var G_ElementIsReadOnly = 403;
var G_ElementIsWriteOnly = 404;
var G_IncorrectDataType = 405;

var G_LastError = G_NoError ;

var commit = false ;

// Strictly SCORM variables.
var score=<?php echo $oItem->get_score(); ?>;
var max=<?php echo $oItem->get_max(); ?>;
var min=<?php echo $oItem->get_min(); ?>;
var lesson_status='<?php echo $oItem->get_status(); ?>';
var session_time='<?php echo $oItem->get_scorm_time('js'); ?>';
var suspend_data = '<?php echo $oItem->get_suspend_data(); ?>';
var lesson_location = '<?php echo $oItem->get_lesson_location(); ?>';
var total_time = '<?php echo $oItem->get_scorm_time('js'); ?>';

// Chamilo internal variables.
var saved_lesson_status = 'not attempted';
var lms_lp_id = <?php echo $oLP->get_id(); ?>;
var lms_item_id = <?php echo $oItem->get_id(); ?>;
//var lms_new_item_id = 0; //temporary value (only there between a load_item() and a LMSInitialize())
var lms_been_synchronized = 0;
var lms_initialized = 0;
var lms_total_lessons = <?php echo $oLP->get_total_items_count(); ?>;
var lms_complete_lessons = <?php echo $oLP->get_complete_items_count(); ?>;
var lms_progress_bar_mode = '<?php echo $oLP->progress_bar_mode; ?>';
if(lms_progress_bar_mode == ''){lms_progress_bar_mode='%';}
var lms_view_id = '<?php echo $oLP->get_view(null, $_user['user_id']); ?>';
if(lms_view_id == ''){ lms_view_id = 1;}
var lms_user_id = '<?php echo $_user['user_id']; ?>';
var lms_next_item = '<?php echo $oLP->get_next_item_id(); ?>';
var lms_previous_item = '<?php echo $oLP->get_previous_item_id(); ?>';
var lms_lp_type = '<?php echo $oLP->get_type(); ?>';
var lms_item_type = '<?php echo $oItem->get_type(); ?>';

// Backup for old values.
var old_score = 0;
var old_max = 0;
var old_min = 0;
var old_lesson_status = '';
var old_session_time = '';
var old_suspend_data = '';
var lms_old_item_id = 0;

function LMSInitialize() {  //this is the initialize function of all APIobjects
    logit_scorm('LMSInitialise()',0);
    lms_initialized=1;
    return('true');
}
function LMSGetValue(param) {
    //logit_scorm("LMSGetValue('"+param+"')",1);
    var result='';
    if(param=='cmi.core._children' || param=='cmi.core_children'){
        result='entry, exit, lesson_status, student_id, student_name, lesson_location, total_time, credit, lesson_mode, score, session_time';
    }else if(param == 'cmi.core.entry'){
        result='';
    }else if(param == 'cmi.core.exit'){
        result='';
    }else if(param == 'cmi.core.lesson_status'){
        if(lesson_status != '') {
            result=lesson_status;
        }
        else{
            result='not attempted';
        }
    }else if(param == 'cmi.core.student_id'){
        result='<?php echo $_user['user_id']; ?>';
    }else if(param == 'cmi.core.student_name'){
    <?php
    $who = addslashes(api_get_person_name($_user['firstName'], $_user['lastName']));
    echo "result='$who';";
    ?>
    }else if(param == 'cmi.core.lesson_location'){
        result=lesson_location;
    }else if(param == 'cmi.core.total_time'){
        result=total_time;
    }else if(param == 'cmi.core.score._children'){
        result='raw,min,max';
    }else if(param == 'cmi.core.score.raw'){
        result=score;
    }else if(param == 'cmi.core.score.max'){
        result=max;
    }else if(param == 'cmi.core.score.min'){
        result=min;
    }else if(param == 'cmi.core.score'){
        result=score;
    }else if(param == 'cmi.core.credit'){
        result='no-credit';
    }else if(param == 'cmi.core.lesson_mode'){
        result='normal';
    }else if(param == 'cmi.suspend_data'){
        result='<?php echo $oItem->get_suspend_data(); ?>';
    }else if(param == 'cmi.launch_data'){
        result='';
    }else if(param == 'cmi.objectives._count'){
        result='<?php echo $oItem->get_view_count(); ?>';
    }
    /*
    // Switch not working??? WTF???
    switch(param) {
        case 'cmi.core._children':
            result='entry, exit, lesson_status, student_id, student_name, lesson_location, total_time, credit, lesson_mode, score, session_time';
            break;
        case 'cmi.core_children':
            result='entry, exit, lesson_status, student_id, student_name, lesson_location, total_time, credit, lesson_mode, score, session_time';
            break;
        case 'cmi.core.entry':
            result='';
            break;
        case 'cmi.core.exit':
            result='';
            break;
        case 'cmi.core.lesson_status':
            if(lesson_status != '') {
                result=lesson_status;
            }
            else{
                result='not attempted';
            }
            break;
        case 'cmi.core.student_id':
            result='<?php echo $_user['user_id']; ?>';
            break;
        case 'cmi.core.student_name':
          <?php
            $who = addslashes(api_get_person_name($_user['firstName'], $_user['lastName']));
            echo "result='$who';";
          ?>	break;
        case 'cmi.core.lesson_location':
            result='';
            break;
        case 'cmi.core.total_time':
            result=total_time;
            break;
        case 'cmi.core.score._children':
            result='raw,min,max';
            break;
        case 'cmi.core.score.raw':
            result=score;
            break;
        case 'cmi.core.score.max':
            result=max;
            break;
        case 'cmi.core.score.min':
            result=min;
            break;
        case 'cmi.core.score':
            result=score;
            break;
        case 'cmi.core.credit':
            result='no-credit';
            break;
        case 'cmi.core.lesson_mode':
            result='normal';
            break;
        case 'cmi.suspend_data':
            result='<?php echo $oItem->get_suspend_data(); ?>';
            break;
        case 'cmi.launch_data':
            result='';
            break;
        case 'cmi.objectives._count':
            result='<?php echo $oItem->get_view_count(); ?>';
            break;
        default:
            result='';
            break;
    }
    */
    logit_scorm("LMSGetValue('"+param+"') returned '"+result+"'",1);
    return result;
}
function LMSSetValue(param, val) {
    logit_scorm("LMSSetValue('"+param+"','"+val+"')",0);
    switch(param) {
    case 'cmi.core.score.raw'		: score= val ;			break;
    case 'cmi.core.score.max'		: max = val;			break;
    case 'cmi.core.score.min'		: min = val;			break;
    case 'cmi.core.lesson_location' : lesson_location = val;break;
    case 'cmi.core.lesson_status'	:
        saved_lesson_status = lesson_status;
        lesson_status = val;
        <?php if ($oLP->mode != 'fullscreen') {
              ?>
        //var update = update_toc(lesson_status,lms_item_id);
        <?php
          } ?>
        break;
    case 'cmi.completion_status'	: lesson_status = val;	break; //1.3
    case 'cmi.core.session_time'	: session_time = val;	break;
    case 'cmi.score.scaled'			: score = val ;			break; //1.3
    case 'cmi.success_status'		: success_status = val; break; //1.3
    case 'cmi.suspend_data'			: suspend_data = val;   break;
    }
    //var update = update_toc();
    //var update_progress = update_progress_bar();
    <?php
    if ($oLP->force_commit == 1) {
        echo "	var mycommit = LMSCommit('force');";
    }
    ?>
    return(true);
}
function savedata(origin) { //origin can be 'commit', 'finish' or 'terminate'
    <?php if ($autocomplete_when_80pct) {
        ?>
    if( ( lesson_status == 'incomplete') && (score >= (0.8*max) ) ){
      lesson_status = 'completed';
    }
    <?php
    }?>
    param = 'id='+lms_item_id+'&origin='+origin+'&score='+score+'&max='+max+'&min='+min+'&lesson_status='+lesson_status+'&time='+session_time+'&suspend_data='+suspend_data;

    url="http://<?php
    $self = api_get_self();
    $url = $_SERVER['HTTP_HOST'].$self;
    $url = substr($url, 0, -14); // 14 is the length of this file's name (/scorm_api.php).
    echo $url;
    ?>/lp_controller.php?<?php echo api_get_cidreq(); ?>&action=save&lp_id=<?php echo $oLP->get_id(); ?>&" + param + "";
    logit_lms('saving data (status='+lesson_status+')',1);
    xajax_save_item(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, score, max, min, lesson_status, session_time, suspend_data, lesson_location);
    //xajax_update_pgs();
    //xajax_update_toc();
}

function LMSCommit(val) {
    logit_scorm('LMSCommit()',0);
    commit = true ;
    savedata('commit');
    return('true');
}
function LMSFinish(val) {
    if ( !commit ) {
        logit_scorm('LMSFinish() (no LMSCommit())',1);
    }
    if ( commit ) {
        logit_scorm('LMSFinish() called',1);
        savedata('finish');
    }
    return('true');
}
function LMSGetLastError() {
    logit_scorm('LMSGetLastError()',1);
    return(G_LastError);
}
function LMSGetErrorString(errCode){
    logit_scorm('LMSGetErrorString()',1);
    return('No error !');
}
function LMSGetDiagnostic(errCode){
    logit_scorm('LMSGetDiagnostic()',1);
    return(API.LMSGetLastError());
}
<?php
/**
 * Chamilo-specific code that deals with event handling and inter-frames
 * messaging/refreshing.
 * Note that from now on, the Chamilo JS code in this library will act as
 * a controller, of the MVC pattern, and receive all requests for frame
 * updates, then redispatch to any frame concerned.
 */
?>
/**
 * Defining the AJAX-object class to be made available from other frames.
 */
function XAJAXobject() {
  this.xajax_switch_item_details=xajax_switch_item_details;
  this.switch_item=switch_item;
}

//it is not sure that the scos use the above declarations

oXAJAX = new XAJAXobject();
oxajax = new XAJAXobject();

/**
 * Cross-browser event handling by Scott Andrew
 * @param	element	Element that needs an event attached
 * @param   string	Event type (load, unload, click, keyDown, ...)
 * @param   string	Function name (the event handler)
 * @param   string	used in addEventListener
 */
function addEvent(elm, evType, fn, useCapture){
    if(elm.addEventListener){
        elm.addEventListener(evType, fn, useCapture);
        return true;
    }else if (elm.attachEvent){
        var r = elm.attachEvent('on' + evType, fn);
    }else{
        elm['on'+evType] = fn;
    }
}
/**
 * Add listeners to the page objects. This has to be defined for
 * the current context as it acts on objects that should exist
 * on the page
 */
function addListeners(){
    //exit if the browser doesn't support ID or tag retrieval
    logit_lms('Entering addListeners()',2);
    if(!document.getElementsByTagName){
        logit_lms("getElementsByTagName not available",2);
        return;
    }
    if(!document.getElementById){
        logit_lms("getElementById not available",2);
        return;
    }
    //assign event handlers to objects
    if(lms_lp_type==1 || lms_item_type=='asset'){
        logit_lms('Chamilo LP or asset',2);
        // If this path is a Chamilo learnpath, then start manual save
        // when something is loaded in there.
        var myelem = document.getElementById('content_id');
        if(!myelem){logit_lms("Impossible to find content_id element in document",2);}
        addEvent(myelem,'unload',chamilo_save_asset,false);
        logit_lms('Added event listener on content_id for unload',2);
    }
    logit_lms('Quitting addListeners()',2);
}
/**
 * Load an item into the content frame:
 * - making sure the previous item status have been saved
 * - first updating the current item ID (to save the right item)
 * - updating the frame src
 */
function load_item(item_id,url){
    if(document.getElementById('content_id'))
    {
        logit_lms('Loading item '+item_id,2);
        var cont_f = document.getElementById('content_id');
        if(cont_f.src){
            lms_old_item_id = lms_item_id;
            var lms_new_item_id = item_id;
            //load new content page into content frame
            if(lms_lp_type==1 || lms_item_type=='asset'){
                chamilo_save_asset();
            }
            cont_f.src = url;
            update_toc('unhighlight',lms_old_item_id);
            update_toc('highlight',item_id);
            /* legacy code
            lms_been_synchronized = 0;
            lms_initialized = 0;
            if(lms_lp_type==1 || lms_item_type=='asset'){
                lms_item_id = lms_new_item_id;
            }*/
            return true;
        }
        logit_lms('cont_f.src has no properties',0);
    }
    logit_lms('content_id has no properties',0);
    return false;
}
/**
 * Save a Chamilo learnpath item's time and mark as completed upon
 * leaving it
 */
function chamilo_save_asset(){
    //var linkparams = 'id='+lms_item_id+'&score='+score+'&max='+max+'&min='+min+'&lesson_status='+lesson_status+'&time='+session_time+'&suspend_data='+suspend_data;
    //var url = "<?php echo api_get_path(WEB_CODE_PATH).'lp/lp_controller.php'; ?>?action=save&" + linkparams + "";
    logit_lms('chamilo_save_asset: '+url,0);
    //frames["message_name"].src = url;
    xajax_save_item(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, score, max, min, lesson_status, session_time, suspend_data, lesson_location);
}
/**
 * Logs information about SCORM messages into the log frame
 * @param	string	Message to log
 * @param	integer Priority (0 for top priority, 3 for lowest)
 */
function logit_scorm(message,priority) {
    if (scorm_logs) {
        log_in_log("SCORM: " + message);
    }
    return false;

    /*if(frames["lp_log_name"] && scorm_logs>priority){
        frames["lp_log_name"].document.getElementById("log_content").innerHTML += "AICC: " + message + "<br/>";
    }*/
}

function log_in_log(message) {
    var ua = $.browser;
    if (ua.mozilla) {
        console.log(message);
    } else {
        if (window.console) {
            window.console.log(message);
        }
    }
}

/**
 * Logs information about LMS activity into the log frame
 * @param	string	Message to log
 * @param	integer Priority (0 for top priority, 3 for lowest)
 */
function logit_lms(message,priority) {
    /*
    if(frames["lp_log_name"] && lms_logs>priority){
        frames["lp_log_name"].document.getElementById("log_content").innerHTML += "LMS: " + message + "<br/>";
    }*/

    if (scorm_logs) {
        log_in_log("LMS: " + message);
    }
}

/**
 * update the Table Of Contents frame, by changing CSS styles, mostly
 * @param	string	Action to be taken
 * @param	integer	Item id to update
 */
function update_toc(update_action,update_id)
{
    <?php if ($oLP->mode != 'fullscreen') {
    ?>
        var myframe = frames["toc_name"];
        var myelem = myframe.document.getElementById("toc_"+update_id);
        var myelemimg = myframe.document.getElementById("toc_img_"+update_id);
        logit_lms('update_toc('+update_action+','+update_id+')',2);
        if(update_id != 0){
            switch(update_action){
                case 'unhighlight':
                    myelem.className = "scorm_item";
                    break;
                case 'highlight':
                    myelem.className = "scorm_item_highlight";
                    break;
                case 'not attempted':
                    if(myelemimg.src != '../img/notattempted.gif'){
                        myelemimg.src = "../img/notattempted.gif";
                        myelemimg.alt = "not attempted";
                    }
                    break;
                case 'incomplete':
                    if(myelemimg.src != '../img/incomplete.png'){
                        myelemimg.src = "../img/incomplete.png";
                        myelemimg.alt = "incomplete";
                    }
                    break;
                case 'completed':
                    if(myelemimg.src != '../img/completed.png'){
                        myelemimg.src = "../img/completed.png";
                        myelemimg.alt = "completed";
                    }
                    break;
                case 'failed':
                    if(myelemimg.src != '../img/failed.png'){
                        myelemimg.src = "../img/failed.png";
                        myelemimg.alt = "failed";
                    }
                    break;
                case 'passed':
                    if(myelemimg.src != '../img/completed.png' && myelemimg.alt != 'passed'){
                        myelemimg.src = "../img/completed.png";
                        myelemimg.alt = "passed";
                    }
                    break;
                case 'browsed':
                    if(myelemimg.src != '../img/completed.png' && myelemimg.alt != 'browsed'){
                        myelemimg.src = "../img/completed.png";
                        myelemimg.alt = "browsed";
                    }
                    break;
                default:
                    logit_lms('Update action unknown',2);
                    break;
            }
        }
        return true;
    <?php
} ?>
    return true;
}
/**
 * Updates the progress bar with the new status. Prevents the need of a page refresh and flickering
 * @param	integer	Number of completed items
 * @param	integer	Number of items in total
 * @param	string  Display mode (absolute 'abs' or percentage '%').Defaults to %
 */
function update_progress_bar(nbr_complete, nbr_total, mode)
{
    logit_lms('update_progress_bar('+nbr_complete+','+nbr_total+','+mode+')',2);
    logit_lms('could update with data: '+lms_lp_id+','+lms_view_id+','+lms_user_id,2);
    var myframe = frames["nav_name"];
    if(myframe){
        if(mode == ''){mode='%';}
        if(nbr_total == 0){nbr_total=1;}
        var percentage = (nbr_complete/nbr_total)*100;
        percentage = Math.round(percentage);

        var progress_bar  = $("#progress_bar_value");
        progress_bar.css('width', percentage +"%");

    /*
        var pr_text  = myframe.document.getElementById('progress_text');
        var pr_full  = myframe.document.getElementById('progress_img_full');

        var pr_empty = myframe.document.getElementById('progress_img_empty');
        pr_full.width = percentage;
        pr_empty.width = 100-percentage;
        */

        var mytext = '';
        switch(mode){
            case 'abs':
                mytext = nbr_complete + '/' + nbr_total;
                break;
            case '%':
            default:
                mytext = percentage + '%';
                break;
        }
        pr_text.innerHTML = mytext;
    }
    return true;
}

/**
 * Updates the message frame with the given string
 */
function update_message_frame(msg_msg)
{
    if(msg_msg==null){msg_msg='';}
    var msg_f = frames["message_name"];
    if(!msg_f.document || !msg_f.document.getElementById('msg_div_id')){
        logit_lms('In update_message_frame() - message frame has no document property',0);
    }else{
        logit_lms('In update_message_frame() - updating frame',0);
        msg_f.document.getElementById('msg_div_id').innerHTML= msg_msg;
    }
}
/**
 * Function that handles the saving of an item and switching from an item to another.
 * Once called, this function should be able to do the whole process of (1) saving the
 * current item, (2) refresh all the values inside the SCORM API object, (3) open the
 * new item into the content_id frame, (4) refresh the table of contents, (5) refresh
 * the progress bar (completion), (6) refresh the message frame
 * @param	integer		Chamilo ID for the current item
 * @param	string		This parameter can be a string specifying the next
 *						item (like 'next', 'previous', 'first' or 'last') or the id to the next item
 */
function switch_item(current_item, next_item){
    /*
    if(!current_item){
        logit_lms('In switch - no current_item defined',0);
    }
    if(!next_item){
        logit_lms('In switch - no next_item defined',0);
    }
    */
    if(lms_item_id == next_item){
        return; //nothing to switch
    }
    //(1) save the current item
    logit_lms('Called switch_item with params '+lms_item_id+' and '+next_item+'',0);
    xajax_save_item(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, score, max, min, lesson_status, session_time, suspend_data, lesson_location);

    //(2) Refresh all the values inside this SCORM API object - use AJAX
    xajax_switch_item_details(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,next_item);

    //(3) open the new item in the content_id frame
    var cont_f = document.getElementById('content_id');
    if(!cont_f){logit_lms('In switch - content frame not found',0);return false;}
    switch(next_item){
        case 'next':
            next_item = lms_next_item;
            break;
        case 'previous':
            next_item = lms_previous_item;
            break;
        default:
            break;
    }
    cont_f.src = 'lp_controller.php?action=content&lp_id='+lms_lp_id+'&item_id='+next_item;

    //(4) refresh table of contents
    /*
    var toc_f = document.getElementById('toc_id');
    if(!toc_f){logit_lms('In switch - toc frame not found',0);return false;}
    var myrefresh = toc_f.src;
    toc_f.src = myrefresh;
    */

    //(5) refresh the progress bar
    /*
    var prg_f = document.getElementById('nav_id');
    if(!prg_f){logit_lms('In switch - navigation frame not found',0);return false;}
    var myrefresh = prg_f.src;
    prg_f.src = myrefresh;
    */

    //(6) refresh the message box (included in switch_item_details)
    return true;
}
/**
 * Save a specific item (with its interactions, if any) into the LMS through
 * an AJAX call. Originally, we used the xajax library. Now we use jQuery.
 * Because of the need to pass an array, we have to build the parameters
 * manually into GET[]
 */
function xajax_save_item(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, score, max, min, lesson_status, session_time, suspend_data, lesson_location, interactions, lms_item_core_exit) {
        params='';
        params += 'lid='+lms_lp_id+'&uid='+lms_user_id+'&vid='+lms_view_id;
        params += '&iid='+lms_item_id+'&s='+score+'&max='+max+'&min='+min;
        params += '&status='+lesson_status+'&t='+session_time;
        params += '&suspend='+suspend_data+'&loc='+lesson_location;
        params += '&core_exit='+lms_item_core_exit;
        interact_string = '';
        for (i in interactions){
            interact_string += '&interact['+i+']=';
            interact_temp = '[';
            for (j in interactions[i]) {
                interact_temp += interactions[i][j]+',';
            }
            interact_temp = interact_temp.substr(0,(interact_temp.length-2)) + ']';
            interact_string += encodeURIComponent(interact_temp);
        }
        //interact_string = encodeURIComponent(interact_string.substr(0,(interact_string.length-1)));
        params += interact_string;
        /*params = {
            'lid': lms_lp_id,
            'uid': lms_user_id,
            'vid': lms_view_id,
            'iid': lms_item_id,
            's': score,
            'max': max,
            'min': min,
            'status': lesson_status,
            't': session_time,
            'suspend': suspend_data,
            'loc': lesson_location,
            'interact': interac_string,
            'core_exit': lms_item_core_exit
        }
        */
        $.ajax({
            type:"POST",
            data: params,
            url: "lp_ajax_save_item.php",
            dataType: "script",
            async: false
            }
        );
}
/**
 * Starts the timer with the server clock time.
 * Originally, we used the xajax library. Now we use jQuery
 */
function xajax_start_timer() {
    $.ajax({
        type: "GET",
        url: "lp_ajax_start_timer.php",
        dataType: "script",
        async: false
    });
}
/**
 * Save a specific item's objectives into the LMS through
 * an AJAX call. Originally, we used the xajax library. Now we use jQuery
 */
function xajax_save_objectives(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,item_objectives) {
        params='';
        params += 'lid='+lms_lp_id+'&uid='+lms_user_id+'&vid='+lms_view_id;
        params += '&iid='+lms_item_id;
        obj_string = '';
        for (i in item_objectives){
            obj_string += '&objectives['+i+']=';
            obj_temp = '[';
            for (j in item_objectives[i]) {
                obj_temp += item_objectives[i][j]+',';
            }
            obj_temp = obj_temp.substr(0,(obj_temp.length-2)) + ']';
            obj_string += encodeURIComponent(obj_temp);
        }
        params += obj_string;
        $.ajax({
            type: "POST",
            data: params,
            url: "lp_ajax_save_objectives.php",
            dataType: "script",
            async: false
        });
}
/**
 * Switch between two items through
 * an AJAX call. Originally, we used the xajax library. Now we use jQuery
 */
function xajax_switch_item_details(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,next_item) {
    params = {
        'lid': lms_lp_id,
        'uid': lms_user_id,
        'vid': lms_view_id,
        'iid': lms_item_id,
        'next': next_item
    }
    $.ajax({
        type: "POST",
        data: params,
        url: "lp_ajax_switch_item.php",
        dataType: "script",
        async: false
    });
}
addEvent(window,'load',addListeners,false);
