<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * API event handler functions for Scorm 1.1 and 1.2 and 1.3 (latter not fully supported)
 * This script is divided into three sections.
 * The first section (below) is the initialisation part.
 * The second section is the SCORM object part
 * The third section defines the event handlers for Chamilo's internal messaging
 * and frames refresh.
 *
 * @author   Denes Nagy <darkden@freemail.hu> (original author - 2003-2004)
 * @author   Yannick Warnier <ywarnier@beeznest.org> (extended and maintained - 2005-2014)
 *
 * @version  v 1.2
 */

// If you open the imsmanifest.xml via local machine (f.ex.: file://c:/...), then the Apiwrapper.js
// of Maritime Navigation when trying to execute this row
//    var result = api.LMSInitialize("");
// get the error response : you are not authorized to call this function

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

require_once __DIR__.'/../inc/global.inc.php';

$file = Session::read('file');
/** @var learnpath $oLP */
$oLP = UnserializeApi::unserialize(
    'lp',
    Session::read('lpobject')
);
if (!is_object($oLP)) {
    error_log('New LP - scorm_api - Could not load oLP object', 0);
    exit;
}
/** @var learnpathItem $oItem */
$oItem = isset($oLP->items[$oLP->current]) ? $oLP->items[$oLP->current] : null;

if (!is_object($oItem)) {
    error_log('New LP - scorm_api - Could not load oItem item', 0);
    exit;
}
$autocomplete_when_80pct = 0;
$user = api_get_user_info();
$userId = api_get_user_id();

$extraParams = '';
if (isset($oLP->lti_launch_id)) {
    $extraParams .= '&lti_launch_id='.Security::remove_XSS($oLP->lti_launch_id);
}

header('Content-type: text/javascript');

?>var scorm_logs=<?php echo (empty($oLP->scorm_debug) or (!api_is_course_admin() && !api_is_platform_admin())) ? '0' : '3'; ?>; //debug log level for SCORM. 0 = none, 1=light, 2=a lot, 3=all - displays logs in log frame
var lms_logs = 0; //debug log level for LMS actions. 0=none, 1=light, 2=a lot, 3=all
var score_as_progress = <?php echo empty($oLP->getUseScoreAsProgress()) ? 'false' : 'true'; ?>;

// API Object initialization (eases access later on)
function APIobject() {
    this.LMSInitialize=LMSInitialize;  //for Scorm 1.2
    this.Initialize=LMSInitialize;     //for Scorm 1.3
    this.LMSGetValue=LMSGetValue;
    this.GetValue=LMSGetValue;
    this.LMSSetValue=LMSSetValue;
    this.SetValue=LMSSetValue;
    this.LMSCommit=LMSCommit;
    this.Commit=LMSCommit;
    this.LMSFinish=LMSFinish;
    this.Finish=LMSFinish;
    this.LMSGetLastError=LMSGetLastError;
    this.GetLastError=LMSGetLastError;
    this.LMSGetErrorString=LMSGetErrorString;
    this.GetErrorString=LMSGetErrorString;
    this.LMSGetDiagnostic=LMSGetDiagnostic;
    this.GetDiagnostic=LMSGetDiagnostic;
    this.Terminate=Terminate;  //only in Scorm 1.3
    this.save_asset = lms_save_asset;
    this.void_save_asset = chamilo_void_save_asset;
}

// it is not sure that the scos use the above declarations. The following
// multiple declarations are to make sure we have an API object for each type of
// SCORM
var API = new APIobject(); //for scorm 1.2
var api = API;
//var API_1484_11 = new APIobject();  //for scorm 1.3
//var api_1484_11 = API_1484_11;

// SCORM-specific Error codes
var G_NoError = 0;
var G_GeneralException = 101;
var G_ServerBusy                = 102; // this is not in the Scorm1.2_Runtime document
var G_InvalidArgumentError = 201;
var G_ElementCannotHaveChildren = 202;
var G_ElementIsNotAnArray = 203;
var G_NotInitialized = 301;
var G_NotImplementedError = 401;
var G_InvalidSetValue = 402;
var G_ElementIsReadOnly = 403;
var G_ElementIsWriteOnly = 404;
var G_IncorrectDataType = 405;

// SCORM-specific Error messages
var G_NoErrorMessage                    = '';
var G_GeneralExceptionMessage           = 'General Exception';
var G_ServerBusyMessage                 = 'Server busy'; // this is not in the Scorm1.2_Runtime document
var G_InvalidArgumentErrorMessage       = 'Invalid argument error';
var G_ElementCannotHaveChildrenMessage  = 'Element cannot have children';
var G_ElementIsNotAnArrayMessage        = 'Element not an array.  Cannot have count';
var G_NotInitializedMessage             = 'Not initialized';
var G_NotImplementedErrorMessage        = 'Not implemented error';
var G_InvalidSetValueMessage            = 'Invalid set value, element is a keyword';
var G_ElementIsReadOnlyMessage          = 'Element is read only';
var G_ElementIsWriteOnlyMessage         = 'Element is write only';
var G_IncorrectDataTypeMessage          = 'Incorrect Data Type';

var olms = new Object();

//the last recorded error message was:
olms.G_LastError = G_NoError ;
olms.G_LastErrorMessage = 'No error';

//this is not necessary and is only provided to make bad Articulate contents
// shut up (and not trigger useless JS messages)
olms.G_LastErrorString = 'No error';

//these variables are provided for better control of the current status in the
// SCORM exchange
olms.commit = false;

// informative array helping to select variables to save, later on
olms.scorm_variables = new Array(
    'cmi.core.score.raw',
    'cmi.core.score.max',
    'cmi.core.score.min',
    'cmi.core.lesson_location',
    'cmi.core.lesson_status',
    'cmi.completion_status',
    'cmi.core.session_time',
    'cmi.score.scaled',
    'cmi.success_status',
    'cmi.suspend_data',
    'cmi.core.exit',
    'interactions'
);

// manage variables to save or not
olms.variable_to_send = new Array();

// temporary list of variables (gets set to true when set through LMSSetValue)
olms.updatable_vars_list = new Array();
// marker of whether the LMSFinish() function was called, which is important for SCORM behaviour
olms.finishSignalReceived = 0;
// marker to remember if the SCO has calles a "set" on lesson_status
olms.statusSignalReceived = 0;

// Strictly scorm variables
olms.score=<?php echo $oItem->get_score(); ?>;
olms.max='<?php echo $oItem->get_max(); ?>';
olms.min='<?php echo $oItem->get_min(); ?>';
olms.lesson_status='<?php echo $oItem->get_status(); ?>';
olms.session_time='<?php echo $oItem->get_scorm_time('js'); ?>';
olms.suspend_data = '<?php echo $oItem->get_suspend_data(); ?>';
olms.lesson_location = '<?php echo $oItem->get_lesson_location(); ?>';
olms.total_time = '<?php echo $oItem->get_scorm_time('js'); ?>';
olms.mastery_score = '<?php echo $oItem->get_mastery_score(); ?>';
olms.launch_data = '<?php echo $oItem->get_launch_data(); ?>';
olms.max_time_allowed = '<?php echo $oItem->get_max_time_allowed(); ?>';
olms.interactions = new Array(<?php echo $oItem->get_interactions_js_array(); ?>);
olms.item_objectives = new Array();
olms.info_lms_item = new Array();

// Chamilo internal variables (not SCORM)
// olms.saved_lesson_status = 'not attempted';
olms.lms_lp_id = <?php echo $oLP->get_id(); ?>;
olms.lms_item_id = <?php echo $oItem->get_id(); ?>;
olms.lms_initialized = 0;
// switch_finished indicates if the switch process is finished (if it has gone
// through LMSInitialize() for the new item. Until then, all LMSSetValue()
// commands received are executed on the *previous/current* item
// This flag is updated in LMSInitialize() and in switch_item()
olms.switch_finished = 0;

//olms.lms_total_lessons = <?php echo $oLP->get_total_items_count(); ?>;
//olms.lms_complete_lessons = <?php echo $oLP->get_complete_items_count(); ?>;
//olms.lms_progress_bar_mode = '<?php echo $oLP->progress_bar_mode; ?>';
//if(lms_progress_bar_mode == ''){lms_progress_bar_mode='%';}

olms.lms_view_id = '<?php echo $oLP->get_view(null, $userId); ?>';
if(olms.lms_view_id == ''){ olms.lms_view_id = 1;}
olms.lms_user_id = '<?php echo $userId; ?>';
olms.lms_next_item = '<?php echo $oLP->get_next_item_id(); ?>';
olms.lms_previous_item = '<?php echo $oLP->get_previous_item_id(); ?>';
olms.lms_lp_type = '<?php echo $oLP->get_type(); ?>';
olms.lms_item_type = '<?php echo $oItem->get_type(); ?>';
olms.lms_item_credit = '<?php echo $oItem->get_credit(); ?>';
olms.lms_item_lesson_mode = '<?php echo $oItem->get_lesson_mode(); ?>';
olms.lms_item_launch_data = '<?php echo addslashes($oItem->get_launch_data()); ?>';
olms.lms_item_core_exit = '<?php echo $oItem->get_core_exit(); ?>';
olms.lms_course_id = '<?php echo $oLP->get_course_int_id(); ?>';
olms.lms_session_id = '<?php echo api_get_session_id(); ?>';
olms.lms_course_code = '<?php echo $oLP->getCourseCode(); ?>';
<?php echo $oLP->get_items_details_as_js('olms.lms_item_types'); ?>

// Following definition of cmi.core.score.raw in SCORM 1.2, "LMS should
// initialize this to an empty string ("") upon initial launch of a SCO. The
// SCO is responsible for setting this value. If an LMSGetValue() is requested
// before the SCO has set this value, then the LMS should return an empty
// string ("")
// As Chamilo initializes this to 0 for non-sco, we need a little hack here.
if (olms.score == 0 && olms.lms_item_type == 'sco' && olms.lesson_status == 'not attempted') {
    olms.score = "";
}

olms.asset_timer = 0;
olms.userfname = '<?php echo addslashes(trim($user['firstname'])); ?>';
olms.userlname = '<?php echo addslashes(trim($user['lastname'])); ?>';
olms.execute_stats = false;

var courseUrl = '?cidReq='+olms.lms_course_code+'&id_session='+olms.lms_session_id+'<?php echo $extraParams; ?>';
var statsUrl = 'lp_controller.php' + courseUrl + '&action=stats';

/**
 * Add the "addListeners" function to the "onload" event of the window and
 * start the timer if necessary (asset)
 */
addEvent(window, 'load', addListeners, false);

// Initialize stuff when the page is loaded
$(function() {
    logit_lms('document.ready event starts');
    logit_lms('These logs are generated by the main/lp/scorm_api.php JS '
        + 'library when the admin has clicked on the debug icon in the '
        + 'learning paths list: '
        + 'lines prefixed with "LMS:" refer to actions taken on the LMS side, '
        + 'while lines prefixed with "SCORM:" refer to actions taken to match '
        + 'the SCORM standard at the JS level.', 3);
    logit_scorm('LMSSetValue calls are shown in red for better visibility.', 0);
    logit_scorm('Other SCORM calls are shown in orange.', 1);
    logit_lms('To add new messages to these logs, use logit_lms() or logit_scorm().');

    olms.info_lms_item[0] = '<?php echo $oItem->get_id(); ?>';
    olms.info_lms_item[1] = '<?php echo $oItem->get_id(); ?>';

    $("#content_id").load(function() {
        logit_lms('#content_id load event starts');
        olms.info_lms_item[0] = olms.info_lms_item[1];

        // Only trigger the LMSInitialize automatically if not SCO
        if (olms.lms_item_types['i'+olms.info_lms_item[1]] != 'sco') {
            LMSInitialize();
        } else {
            logit_lms('Content type is SCO and is responsible to launch LMSInitialize() on its own - Skipping',2);
        }
    });
});

// This code was moved inside LMSInitialize()
if (olms.lms_item_type != 'sco') {
    xajax_start_timer();
}

/**
 * The following section represents a set of mandatory functions for SCORM
 */
/**
 * Function called mandatorily by the SCORM content to start the SCORM comm
 * This is the initialize function of all APIobjects
 * @return  string  'true' or 'false'. Returning a string is mandatory (SCORM).
 */
function LMSInitialize() {
    /* load info for this new item by calling the js_api_refresh command in
     * the message frame. The message frame will update the JS variables by
     * itself, in JS, by doing things like top.lesson_status = 'not attempted'
     * and that kind of stuff, so when the content loads in the content frame
     * it will have all the correct variables set
     */

    logit_scorm('LMSInitialize()');

    olms.G_LastError = G_NoError ;
    olms.G_LastErrorMessage = 'No error';
    olms.lms_initialized = 0;
    olms.finishSignalReceived = 0;
    olms.statusSignalReceived = 0;
    olms.switch_finished = 0;
    // if there are more parameters than ""
    if (arguments.length > 1) {
        olms.G_LastError        = G_InvalidArgumentError;
        olms.G_LastErrorMessage = G_InvalidArgumentErrorMessage;
        logit_scorm('Error '+ G_InvalidArgumentError + G_InvalidArgumentErrorMessage, 0);
        return('false');
    } else {
        //reinit the list of modified variables
        reinit_updatable_vars_list();

        // Get LMS values for this item
        var params = {
            'lid': olms.lms_lp_id,
            'uid': olms.lms_user_id,
            'vid': olms.lms_view_id,
            'iid': olms.lms_item_id
        };

        $.ajax({
            type: "POST",
            url: "lp_ajax_initialize.php" + courseUrl,
            data: params,
            dataType: 'script',
            async: false,
            success:function(data) {
                $('video:not(.skip), audio:not(.skip)').mediaelementplayer();
            }
        });

        olms.lms_initialized = 1;
        olms.switch_finished = 1;

        // log a more complete object dump when initializing, so we know what data hasn't been cleaned
        var log = '\nitem             : '+ olms.lms_item_id
                 + '\nitem_type       : '+ olms.lms_item_type
                 + '\nscore           : '+ olms.score
                 + '\nmax             : '+ olms.max
                 + '\nmin             : '+ olms.min
                 + '\nlesson_status   : '+ olms.lesson_status
                 + '\nsession_time    : '+ olms.session_time
                 + '\nlesson_location : '+ olms.lesson_location
                 + '\nsuspend_data    : '+ olms.suspend_data
                 + '\ntotal_time      : '+ olms.total_time
                 + '\nmastery_score   : '+ olms.mastery_score
                 + '\nmax_time_allowed: '+ olms.max_time_allowed
                 + '\ncredit          : '+ olms.lms_item_credit
                 + '\nlms_lp_id       : '+ olms.lms_lp_id
                 + '\nlms_user_id     : '+ olms.lms_user_id
                 + '\nlms_view_id     : '+ olms.lms_view_id
                 + '\nfinishSignalReceived : '+ olms.finishSignalReceived
                 + '\nstatusSignalReceived : '+ olms.statusSignalReceived
                ;

        logit_scorm('LMSInitialize() with params: '+log);

        if(olms.lms_item_type == 'sco'){
            $("#tab-iframe").removeClass();
            $("#tab-iframe").addClass("tab-content iframe_"+olms.lms_item_type);
        }

        if (olms.lms_item_type != 'sco') {
            xajax_start_timer();
        }

        if (olms.lms_item_type == 'quiz' || olms.lms_item_type == 'h5p') {
            update_toc(olms.lesson_status, olms.lms_item_id);
        }

        <?php
        $glossaryExtraTools = api_get_setting('show_glossary_in_extra_tools');
        $fixLinkSetting = api_get_configuration_value('lp_fix_embed_content');
        $showGlossary = in_array($glossaryExtraTools, ['true', 'lp', 'exercise_and_lp']);
        if ($showGlossary) {
            if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
                ?>
                if (olms.lms_item_type == 'sco') {
                    attach_glossary_into_scorm('automatic');
                } else {
                    attach_glossary_into_scorm('manual');
                }
                <?php
            } elseif (api_get_setting('show_glossary_in_documents') == 'isautomatic') {
                ?>
                attach_glossary_into_scorm('automatic');
            <?php
            } ?>
        <?php
        } ?>

        <?php if ($fixLinkSetting) {
            ?>
            attach_glossary_into_scorm('fix_links');
        <?php
        } ?>
        return('true');
    }
}

/**
 * Twin sister of LMSInitialize(). Only provided for backwards compatibility.
 * this is the initialize function of all APIobjects
 */
function Initialize() {
    return LMSInitialize();
}

/**
 * Gets a value in the current SCORM context and returns it to the calling SCO
 * @param   string  The name of the value we want
 * @return  string  All return values must be string (see SCORM)
 */
function LMSGetValue(param) {
    olms.G_LastError = G_NoError;
    olms.G_LastErrorMessage = 'No error';
    var result = '';
    // the LMSInitialize is missing
    if (olms.lms_initialized == 0) {
         if (param == 'cmi.core.score.raw') {
             return '';
         }
         olms.G_LastError = G_NotInitialized;
         olms.G_LastErrorMessage = G_NotInitializedMessage;
         logit_scorm('LMSGetValue('+param+') on item id '+olms.lms_item_id+':<br />=> Error '+ G_NotInitialized + ' ' +G_NotInitializedMessage, 0);
         return '';
    }

    // Chamilo does not support these SCO object properties
    if (param == 'cmi.student_preference.text' ||
        param == 'cmi.student_preference.language' ||
        param == 'cmi.student_preference.speed' ||
        param == 'cmi.student_preference.audio' ||
        param == 'cmi.student_preference._children' ||
        param == 'cmi.student_data.time_limit_action' ||
        param == 'cmi.comments' ||
        param == 'cmi.comments_from_lms' ||
        /* The following properties were part of SCORM 1.0 or never implemented at all
         but seem to react badly to Captivate content producer when not defined */
        param == 'cmi.student_demographics._children' ||
        param == 'cmi.student_demographics.city' ||
        param == 'cmi.student_demographics.class' ||
        param == 'cmi.student_demographics.company' ||
        param == 'cmi.student_demographics.country' ||
        param == 'cmi.student_demographics.experience' ||
        param == 'cmi.student_demographics.familiar_name' ||
        param == 'cmi.student_demographics.instructor_name' ||
        param == 'cmi.student_demographics.title' ||
        param == 'cmi.student_demographics.native_language' ||
        param == 'cmi.student_demographics.state' ||
        param == 'cmi.student_demographics.street_address' ||
        param == 'cmi.student_demographics.telephone' ||
        param == 'cmi.student_demographics.years_experience' ) {
        // the value is not supported
        olms.G_LastError = G_NotImplementedError  ;
        olms.G_LastErrorString = G_NotImplementedErrorMessage;
        logit_scorm("LMSGetValue ('"+param+"') Error '"+G_NotImplementedErrorMessage+"'",1);
        result = '';
        return result;
    }
    if (param=='cmi.student_demographics.first_name') {
        result=olms.userfname;
    } else if(param=='cmi.student_demographics.last_name') {
        result=olms.userlname;
        // ---- cmi.core._children
    } else if(param=='cmi.core._children' || param=='cmi.core_children'){
        result='entry, exit, lesson_status, student_id, student_name, lesson_location, total_time, credit, lesson_mode, score, session_time';
    } else if(param == 'cmi.core.entry'){
        // ---- cmi.core.entry
        if(olms.lms_item_core_exit=='none') {
            result='ab-initio';
        } else if(olms.lms_item_core_exit=='suspend') {
            result='resume';
        } else {
            result='';
        }
    } else if(param == 'cmi.core.exit') {
        // ---- cmi.core.exit
        result='';
        olms.G_LastError = G_ElementIsWriteOnly;
    } else if(param == 'cmi.core.session_time'){
        result='';
        olms.G_LastError = G_ElementIsWriteOnly;
    } else if(param == 'cmi.core.lesson_status'){
        // ---- cmi.core.lesson_status
        if (olms.lesson_status != '') {
            result = olms.lesson_status;
        } else {
            //result='not attempted';
        }
    } else if(param == 'cmi.core.student_id' || param == 'cmi.learner_id') { // cmi.learner_id widens support for SCORM 2004
        // ---- cmi.core.student_id
        result='<?php echo learnpath::getUserIdentifierForExternalServices(); ?>';
    } else if(param == 'cmi.core.student_name' || param == 'cmi.learner_name') { // cmi.learner_name widens support for SCORM 2004
        // ---- cmi.core.student_name
        <?php
          $who = addslashes(trim($user['lastname']).', '.trim($user['firstname']));
          echo "result='$who';";
        ?>
    } else if(param == 'cmi.core.lesson_location'){
        // ---- cmi.core.lesson_location
        result=olms.lesson_location;
    } else if(param == 'cmi.core.total_time'){
        // ---- cmi.core.total_time
        result=olms.total_time;
    } else if(param == 'cmi.core.score._children'){
        // ---- cmi.core.score._children
        result='raw,min,max';
    } else if(param == 'cmi.core.score.raw'){
        // ---- cmi.core.score.raw
        result=olms.score;
    } else if(param == 'cmi.core.score.max'){
        // ---- cmi.core.score.max
        result=olms.max;
    } else if(param == 'cmi.core.score.min'){
        // ---- cmi.core.score.min
        result=olms.min;
    } else if(param == 'cmi.core.score'){
        // ---- cmi.core.score -- non-standard item, provided as cmi.core.score.raw just in case
        result=olms.score;
    } else if(param == 'cmi.core.credit'){
        // ---- cmi.core.credit
        result = olms.lms_item_credit;
    } else if(param == 'cmi.core.lesson_mode'){
        // ---- cmi.core.lesson_mode
        result = olms.lms_item_lesson_mode;
    } else if(param == 'cmi.suspend_data'){
        // ---- cmi.suspend_data
        olms.suspend_data = get_local_suspend_data(); // check localStorage suspend data, if available
        result = olms.suspend_data;
    } else if(param == 'cmi.launch_data'){
    // ---- cmi.launch_data
        result = olms.lms_item_launch_data;
    } else if(param == 'cmi.objectives._children'){
    // ---- cmi.objectives._children
        result = 'id,score,status';
    } else if(param == 'cmi.objectives._count'){
    // ---- cmi.objectives._count
        //result='<?php echo $oItem->get_view_count(); ?>';
        result = olms.item_objectives.length;
    } else if(param.substring(0,15)== 'cmi.objectives.'){
        var myres = '';
        if(myres = param.match(/cmi.objectives.(\d+).(id|score|status|_children)(.*)/)) {
            var obj_id = myres[1];
            var req_type = myres[2];
            if(olms.item_objectives[obj_id]==null) {
                if(req_type == 'id') {
                    result = '';
                } else if(req_type == '_children'){
                    result = 'id,score,status';
                } else if(req_type == 'score'){
                    if(myres[3]==null) {
                        result = '';
                        olms.G_LastError = G_NotImplementedError;
                        olms.G_LastErrorString = 'Not implemented yet';
                    }else if (myres[3] == '._children'){
                        result = 'raw,min,max'; //non-standard, added for NetG
                    }else if (myres[3] == '.raw'){
                        result = '';
                    }else if (myres[3] == '.max'){
                        result = '';
                    }else if (myres[3] == '.min'){
                        result = '';
                    }else{
                        result = '';
                        olms.G_LastError = G_NotImplementedError;
                        olms.G_LastErrorString = 'Not implemented yet';
                    }
                } else if(req_type == 'status'){
                    result = 'not attempted';
                }
           } else {
                //the object is not null
                if(req_type == 'id') {
                    result = olms.item_objectives[obj_id][0];
                } else if(req_type == '_children'){
                    result = 'id,score,status';
                } else if(req_type == 'score'){
                    if(myres[3]==null) {
                        result = '';
                        olms.G_LastError = G_NotImplementedError;
                        olms.G_LastErrorString = 'Not implemented yet';
                    } else if (myres[3] == '._children'){
                        result = 'raw,min,max'; //non-standard, added for NetG
                    } else if (myres[3] == '.raw'){
                        if(olms.item_objectives[obj_id][2] != null)
                        {
                            result = olms.item_objectives[obj_id][2];
                        }else{
                            result = '';
                        }
                    } else if (myres[3] == '.max'){
                        if(olms.item_objectives[obj_id][3] != null) {
                            result = olms.item_objectives[obj_id][3];
                        }else{
                            result = '';
                        }
                    } else if (myres[3] == '.min') {
                        if(olms.item_objectives[obj_id][4] != null) {
                            result = olms.item_objectives[obj_id][4];
                        } else {
                            result = '';
                        }
                    } else{
                        result = '';
                        olms.G_LastError = G_NotImplementedError;
                        olms.G_LastErrorString = 'Not implemented yet';
                    }
                } else if(req_type == 'status'){
                    if(olms.item_objectives[obj_id][1] != null) {
                        result = olms.item_objectives[obj_id][1];
                    } else {
                        result = 'not attempted';
                    }
                }
            }
        }
    } else if(param == 'cmi.student_data._children'){
        // ---- cmi.student_data._children
        result = 'mastery_score,max_time_allowed';
    } else if(param == 'cmi.student_data.mastery_score'){
        // ---- cmi.student_data.mastery_score
        result = olms.mastery_score;
    } else if(param == 'cmi.student_data.max_time_allowed'){
        // ---- cmi.student_data.max_time_allowed
        result = olms.max_time_allowed;
    } else if(param == 'cmi.interactions._count'){
        // ---- cmi.interactions._count
        result = olms.interactions.length;
    } else if(param == 'cmi.interactions._children'){
        // ---- cmi.interactions._children
        result = 'id,time,type,correct_responses,weighting,student_response,result,latency';
    } else{
        // ---- anything else
        // Invalid argument error
        olms.G_LastError = G_InvalidArgumentError ;
        olms.G_LastErrorString = G_InvalidArgumentErrorMessage;
        logit_scorm("LMSGetValue  ('"+param+"') Error '"+G_InvalidArgumentErrorMessage+"'",1);
        result = '';
        return result;
    }

    logit_scorm("LMSGetValue ('"+param+"') returned '"+result+"'",1);

    return result;
}

/**
 * Twin sister of LMSGetValue(). Only provided for backwards compatibility.
 */
function GetValue(param) {
    return LMSGetValue(param);
}

/**
 * Sets a SCORM variable's value through a call from the SCO.
 * @param   string  The SCORM variable's name
 * @param   string  The SCORM variable's new value
 * @param   string  'true','false' or an error code
 */
function LMSSetValue(param, val) {
    logit_scorm("LMSSetValue ('"+param+"','"+val+"')",0);
    logit_scorm("Checking olms.lms_item_id " + olms.lms_item_id);

    olms.commit = true; //value has changed, need to re-commit
    olms.G_LastError = G_NoError ;
    olms.G_LastErrorMessage = 'No error';
    return_value = 'false';

    if (param == "cmi.core.score.raw") {
        olms.score= val;
        olms.updatable_vars_list['cmi.core.score.raw']=true;
        if (score_as_progress) {
            update_progress_bar(val, olms.max, '%');
        }
        return_value='true';
    } else if ( param == "cmi.core.score.max") {
        olms.max = val;
        olms.updatable_vars_list['cmi.core.score.max']=true;
        return_value='true';
    } else if ( param == "cmi.core.score.min") {
        olms.min = val;
        olms.updatable_vars_list['cmi.core.score.min']=true;
        return_value='true';
    } else if ( param == "cmi.core.lesson_location" ) {
        olms.lesson_location = val;
        olms.updatable_vars_list['cmi.core.lesson_location']=true;
        return_value = 'true';
    } else if ( param == "cmi.core.lesson_status" ) {
        olms.lesson_status = val;
        olms.updatable_vars_list['cmi.core.lesson_status'] = true;
        olms.statusSignalReceived = 1;
        return_value='true';
    } else if ( param == "cmi.completion_status" ) {
        olms.lesson_status = val;
        olms.updatable_vars_list['cmi.completion_status']=true;
        return_value='true'; //1.3
    } else if ( param == "cmi.core.session_time" ) {
        olms.session_time = val;
        olms.updatable_vars_list['cmi.core.session_time']=true;
        return_value='true';
    } else if ( param == "cmi.score.scaled") { //1.3
        if (val<=1 && val>=-1) {
            olms.score = val ;
            olms.updatable_vars_list['cmi.score.scaled']=true;
            return_value='true';
        } else {
            return_value='false';
        }
    } else if ( param == "cmi.success_status" ) {
        success_status = val;
        olms.updatable_vars_list['cmi.success_status']=true;
        return_value='true'; //1.3
    } else if ( param == "cmi.suspend_data") {
        olms.suspend_data = val;
        olms.updatable_vars_list['cmi.suspend_data'] = true;
        save_suspend_data_in_local(); // save to local storage if available
        return_value='true';
    } else if ( param == "cmi.core.exit" || param == "cmi.exit" ) {
        //cmi.exit for SCORM 1.3
        olms.lms_item_core_exit = val;
        olms.updatable_vars_list['cmi.core.exit']=true;
        return_value='true';
    } else if ( param == "cmi.core.student_id" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.core.student_name" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.core.credit" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.core.entry" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.core.total_time" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.core.lesson_mode" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.comments_from_lms" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.student_data.time_limit_action" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.student_data.mastery_score" ) {
       olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.student_data.max_time_allowed" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.student_preference._children" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else if ( param == "cmi.launch_data" ) {
        olms.G_LastError = G_ElementIsReadOnly;
    } else {
        var myres = new Array();
        if(myres = param.match(/cmi.interactions.(\d+).(id|time|type|correct_responses|weighting|student_response|result|latency)(.*)/)) {
            olms.updatable_vars_list['interactions']=true;
            elem_id = myres[1];
             //interactions setting should start at 0
            if(elem_id > olms.interactions.length) {
                /*
                olms.G_LastError = G_InvalidArgumentError;
                olms.G_LastErrorString = 'Invalid argument (interactions)';
                return_value = false;
                */
                olms.interactions[0] = ['0','','','','','','',''];
            }
            if(olms.interactions[elem_id] == null) {
                olms.interactions[elem_id] = ['','','','','','','',''];
                //id(0), type(1), time(2), weighting(3),correct_responses(4),student_response(5),result(6),latency(7)
                olms.interactions[elem_id][4] = new Array();
            }
            elem_attrib = myres[2];
            switch (elem_attrib) {
                case "id":
                    olms.interactions[elem_id][0] = val;
                    logit_scorm("Interaction "+elem_id+"'s id updated",2);
                    return_value='true';
                    break;
                case "time":
                    olms.interactions[elem_id][2] = val;
                    logit_scorm("Interaction "+elem_id+"'s time updated",2);
                    return_value='true';
                    break;
                case "type":
                    olms.interactions[elem_id][1] = val;
                    logit_scorm("Interaction "+elem_id+"'s type updated",2);
                    return_value='true';
                    break;
                case "correct_responses":
                    // Add at the end of the array
                    olms.interactions[elem_id][4][olms.interactions[elem_id][4].length] = val;
                    logit_scorm("Interaction "+elem_id+"'s correct_responses not updated",2);
                    return_value='true';
                    break;
                case "weighting":
                    olms.interactions[elem_id][3] = val;
                    logit_scorm("Interaction "+elem_id+"'s weighting updated",2);
                    return_value='true';
                    break;
                case "student_response":
                    olms.interactions[elem_id][5] = ''+val;
                    logit_scorm("Interaction "+elem_id+"'s student_response updated",2);
                    return_value='true';
                    break;
                case "result":
                    olms.interactions[elem_id][6] = val;
                    logit_scorm("Interaction "+elem_id+"'s result updated",2);
                    return_value='true';
                    break;
                case "latency":
                    olms.interactions[elem_id][7] = val;
                    logit_scorm("Interaction "+elem_id+"'s latency updated",2);
                    return_value='true';
                    break;
                default:
                    olms.G_LastError = G_NotImplementedError;
                    olms.G_LastErrorString = 'Not implemented yet';
            }
        } else if(param.substring(0,15)== 'cmi.objectives.') {
            var myres = '';
            olms.updatable_vars_list['objectives']=true;
            if(myres = param.match(/cmi.objectives.(\d+).(id|score|status)(.*)/)) {
                obj_id = myres[1];
                //objectives setting should start at 0
                if(obj_id > olms.item_objectives.length) {
                    olms.G_LastError = G_InvalidArgumentError;
                    olms.G_LastErrorString = 'Invalid argument (objectives)';
                    return_value = false;
                } else {
                    req_type = myres[2];
                    if(obj_id == null || obj_id == '') {
                        ;//do nothing
                    } else {
                        if(olms.item_objectives[obj_id]==null) {
                            olms.item_objectives[obj_id] = ['','','','',''];
                        }
                        if (req_type == "id") {
                            //olms.item_objectives[obj_id][0] = val.substring(51,57);
                            olms.item_objectives[obj_id][0] = val;
                            logit_scorm("Objective "+obj_id+"'s id updated",2);
                            return_value = 'true';
                        } else if ( req_type == "score" ) {
                            if (myres[3] == '._children'){
                                return_value = '';
                                olms.G_LastError = G_InvalidSetValue;
                                olms.G_LastErrorString = 'Invalid set value, element is a keyword';
                            } else if (myres[3] == '.raw'){
                                olms.item_objectives[obj_id][2] = val;
                                logit_scorm("Objective "+obj_id+"'s score raw updated",2);
                                return_value = 'true';
                            } else if (myres[3] == '.max'){
                                olms.item_objectives[obj_id][3] = val;
                                logit_scorm("Objective "+obj_id+"'s score max updated",2);
                                return_value = 'true';
                            } else if (myres[3] == '.min'){
                                olms.item_objectives[obj_id][4] = val;
                                logit_scorm("Objective "+obj_id+"'s score min updated",2);
                                return_value = 'true';
                            } else {
                                return_value = '';
                                olms.G_LastError = G_NotImplementedError;
                                olms.G_LastErrorString = 'Not implemented yet';
                            }
                        } else if ( req_type == "status" ) {
                            olms.item_objectives[obj_id][1] = val;
                            logit_scorm("Objective "+obj_id+"'s status updated",2);
                            return_value = 'true';
                        } else {
                            olms.G_LastError = G_NotImplementedError;
                            olms.G_LastErrorString = 'Not implemented yet';
                        }
                    }
                }
            }
        } else {
            olms.G_LastError = G_NotImplementedError;
            olms.G_LastErrorString = G_NotImplementedErrorMessage;
        }
    }
    <?php
    if ($oLP->force_commit == 1) {
        echo " var mycommit = LMSCommit('force');";
    }
    ?>
    return return_value;
}

/**
 * Twin sister of LMSSetValue(). Only provided for backwards compatibility.
 */
function SetValue(param, val) {
    return LMSSetValue(param, val);
}

/**
 * Saves the current data from JS memory to the LMS database
 */
function savedata(item_id) {
    var forceIframeSave = arguments.length > 1 && arguments[1] !== undefined
        ? arguments[1]
        : 0;

    // Origin can be 'commit', 'finish' or 'terminate' (depending on the calling function)
    logit_lms('function savedata(' + item_id + ')', 3);

    // Status is NOT modified here see the lp_ajax_save_item.php file
    if (olms.lesson_status != '') {
        //olms.updatable_vars_list['cmi.core.lesson_status'] = true;
    }

    old_item_id = olms.info_lms_item[0];
    var item_to_save = olms.lms_item_id;
    logit_lms('item_to_save (original value): ' + item_to_save, 3);

    // If saving session_time value, we assume that all the new info is about
    // the old item, not the current one
    // if (olms.session_time != '' && olms.session_time != '0') {
    if (olms.switch_finished == 0 && forceIframeSave == 0) {
        logit_lms('item_to_save (changed to): ' + old_item_id, 3);
        item_to_save = old_item_id;
    }

    xajax_save_item_scorm(
        olms.lms_lp_id,
        olms.lms_user_id,
        olms.lms_view_id,
        item_to_save,
        olms.lms_session_id,
        olms.lms_course_id,
        olms.finishSignalReceived,
        olms.userNavigatesAway,
        olms.statusSignalReceived,
        false,
        forceIframeSave
    );

    olms.info_lms_item[1] = olms.lms_item_id;
    if (olms.item_objectives.length > 0) {
        xajax_save_objectives(
            olms.lms_lp_id,
            olms.lms_user_id,
            olms.lms_view_id,
            old_item_id,
            olms.item_objectives
        );
    }
    olms.execute_stats = false;
    // Clean array
    olms.variable_to_send = new Array();
}

/**
 * Send the Commit signal to the LMS (save the data for this element without
 * closing the current process)
 * From SCORM 1.2 RTE: If the API Adapter is caching values received from the
 * SCO via an LMSSetValue(), this call requires that any values not yet
 * persisted by the LMS be persisted.
 * @param   string      Must be empty string for conformance with SCORM 1.2
 */
function LMSCommit(val) {
    logit_scorm('LMSCommit() val:' + val, 0);

    olms.G_LastError = G_NoError ;
    olms.G_LastErrorMessage = 'No error';
    let forceIframeSave = 0;
    if (val && 'iframe' == val) {
        forceIframeSave = 1;
    }

    console.log(forceIframeSave);
    savedata(olms.lms_item_id, forceIframeSave);

    //reinit_updatable_vars_list();
    logit_scorm('LMSCommit() end ', 0);

    return('true');
}

/**
 * Twin sister of LMSCommit(). Only provided for backwards compatibility.
 */
function Commit(val) {
    return LMSCommit(val);
}

/**
 * Send the closure signal to the LMS. This saves the data and closes the current SCO.
 * From SCORM 1.2 RTE: The SCO must call this when it has determined that it no
 * longer needs to communicate with the LMS, if it successfully called
 * LMSInitialize at any previous point.  This call signifies two things:
 * 1.The SCO can be assured that any data set using LMSSetValue() calls has been persisted by the LMS.
 * 2.The SCO has finished communicating with the LMS.
 * @param   string
 */
function LMSFinish(val) {
    olms.G_LastError = G_NoError ;
    olms.G_LastErrorMessage = 'No error';
    olms.finishSignalReceived = 1;
    // if olms.commit == false, then the SCORM didn't ask for a commit, so we
    // should at least report that
    if ( !olms.commit ) {
        logit_scorm('LMSFinish() (no LMSCommit())',1);
    }

    logit_scorm('LMSFinish() called on item ' + olms.lms_item_id, 0);
    savedata(olms.lms_item_id);

    //reinit the commit detector flag
    olms.commit = false;

    //reinit the list of modified variables
    reinit_updatable_vars_list();
    return('true');
}

/**
 * Twin sister of LMSFinish(). Only provided for backwards compatibility.
 */
function Finish(val) {
    return LMSFinish(val);
}

/**
 * Returns the last error code as a string
 * @return  string  Error code
 */
function LMSGetLastError() {
    var error = olms.G_LastError.toString();
    logit_scorm('LMSGetLastError() returned: ' + error, 1);
    return error;
}

/**
 * Twin sister of LMSGetLastError(). Only provided for backwards compatibility.
 */
function GetLastError() {
    return LMSGetLastError();
}

/**
 * Returns the last error code literal for a given error code
 * @param   int     Error code
 * @return  string  Last error
 */
function LMSGetErrorString(errCode){
    logit_scorm('LMSGetErrorString()',1);
    return(olms.G_LastErrorString);
}

/**
 * Twin sister of LMSGetErrorString(). Only provided for backwards compatibility.
 */
function GetErrorString(errCode){
    return LMSGetErrorString(errCode);
}

/**
 * Returns a more explanatory, full English, error message
 * @param   int     Error code
 * @return  string  Diagnostic
 */
function LMSGetDiagnostic(errCode){
    logit_scorm('LMSGetDiagnostic()',1);
    return API.LMSGetLastError();
}

/**
 * Twin sister of LMSGetDiagnostic(). Only provided for backwards compatibility.
 */
function GetDiagnostic(errCode){
    return LMSGetDiagnostic(errCode);
}

/**
 * Acts as a "commit"
 * This function is not standard SCORM 1.2 and is probably deprecated in all
 * meanings of the term.
 * @return  string  'true' or 'false', depening on whether the LMS has initialized the SCORM process or not
 */
function Terminate() {
    if (olms.lms_initialized == 0) {
        olms.G_LastError 		= G_NotInitialized;
        olms.G_LastErrorMessage = G_NotInitializedMessage;
        logit_scorm('Error '+ G_NotInitialized + G_NotInitializedMessage, 0);
        return('false');
    } else {
        logit_scorm('Terminate()',0);
        olms.G_LastError = G_NoError ;
        olms.G_LastErrorMessage = 'No error';
        olms.commit = true;
        savedata(olms.lms_item_id);
        return ('true');
    }
}

/**
 * LMS-specific code that deals with event handling and inter-frames
 * messaging/refreshing.
 * Note that from now on, the LMS JS code in this library will act as
 * a controller, of the MVC pattern, and receive all requests for frame
 * updates, then redispatch to any frame concerned.
 */
/**
 * Defining the AJAX-object class to be made available from other frames
 */
function XAJAXobject() {
    this.xajax_switch_item_details = xajax_switch_item_details;
    this.switch_item = switch_item;
    this.xajax_save_objectives = xajax_save_objectives;
    this.xajax_save_item = xajax_save_item;
}

/**
 * Cross-browser event handling by Scott Andrew
 * @param	element	Element that needs an event attached
 * @param   string	Event type (load, unload, click, keyDown, ...)
 * @param   string	Function name (the event handler)
 * @param   string	used in addEventListener
 */
function addEvent(elm, evType, fn, useCapture){
    if (elm.addEventListener){
        elm.addEventListener(evType, fn, useCapture);
        return true;
    } else if (elm.attachEvent) {
        var r = elm.attachEvent('on' + evType, fn);
    } else{
        elm['on'+evType] = fn;
    }
}

function lastCall() {
    console.log('lastCall');
    savedata(olms.lms_item_id);
    xajax_save_item_scorm(
        olms.lms_lp_id,
        olms.lms_user_id,
        olms.lms_view_id,
        olms.lms_item_id,
        olms.lms_session_id,
        olms.lms_course_id,
        olms.finishSignalReceived,
        1,
        olms.statusSignalReceived,
        1
    );
}

/**
 * Add listeners to the page objects. This has to be defined for
 * the current context as it acts on objects that should exist
 * on the page
 * possibly deprecated
 * @todo Try to use $(document).unload(lms_save_asset()) instead of the addEvent() method
 */
function addListeners(){
    //exit if the browser doesn't support ID or tag retrieval
    logit_lms('Entering addListeners()', 3);
    if (!document.getElementsByTagName){
        logit_lms("getElementsByTagName not available", 2);
        return;
    }
    if (!document.getElementById){
        logit_lms("getElementById not available", 2);
        return;
    }
    //assign event handlers to objects
    if (olms.lms_item_type != 'sco') {
        logit_lms('Chamilo LP or asset');
        //if this path is a Chamilo learnpath, then start manual save
        //when something is loaded in there
        //addEvent(window, 'unload', lms_save_asset,false);
        $(window).on('unload', function(e){
            lms_save_asset();
            logit_lms('Unload call', 3);
        });
        logit_lms('Added event listener lms_save_asset() on window unload', 3);
    }

    if (olms.lms_item_type=='sco') {
        //window.addEventListener('beforeunload', lastCall);
        window.addEventListener('beforeunload', function (e) {
            var preventsBeforeUnload = <?php echo (int) api_get_configuration_value('lp_prevents_beforeunload'); ?>;

            if (preventsBeforeUnload) {
                e.preventDefault();
            }

            console.log('beforeunload');
            lastCall();
            logit_lms('beforeunload called', 3);

            if (preventsBeforeUnload) {
                e.returnValue = 'true';
            } else {
                delete e['returnValue'];
            }
        });

        $(window).on('unload', function(e) {
            console.log('unload');
            savedata(olms.lms_item_id);
            logit_lms('unload called', 3);
            lastCall();
        });
        logit_lms('Added unload savedata() on window unload', 3);
    }
    logit_lms('Quitting addListeners()');
}

/**
 * Save a Chamilo learnpath item's time and mark as completed upon
 * leaving it
 */
function lms_save_asset() {
    // only for Chamilo lps
    if (olms.execute_stats) {
        olms.execute_stats = false;
    } else {
        olms.execute_stats = true;
    }

    //For scorms do not show stats
    if (olms.lms_lp_type == 2 && olms.lms_lp_item_type != 'document') {
       olms.execute_stats = false;
    }

    if (olms.lms_item_type == 'quiz' || olms.lms_item_type == 'h5p') {
        olms.execute_stats = true;
    }

    if (olms.lms_item_type != 'sco') {
        logit_lms('lms_save_asset');
        logit_lms('execute_stats :'+ olms.execute_stats);
        xajax_save_item(
            olms.lms_lp_id,
            olms.lms_user_id,
            olms.lms_view_id,
            olms.lms_item_id,
            olms.score,
            olms.max,
            olms.min,
            olms.lesson_status,
            olms.session_time,
            olms.suspend_data,
            olms.lesson_location,
            olms.interactions,
            olms.lms_item_core_exit,
            olms.lms_item_type,
            olms.session_id,
            olms.course_id
        );
        if (olms.item_objectives.length>0) {
            xajax_save_objectives(
                olms.lms_lp_id,
                olms.lms_user_id,
                olms.lms_view_id,
                olms.lms_item_id,
                olms.item_objectives
            );
        }
    }
}

/**
 * Save a Chamilo learnpath item's time and mark as completed upon leaving it.
 * Same function as lms_save_asset() but saves it with empty params
 * to use values set from another side in the database. Only used by Chamilo quizzes.
 * Also save the score locally because it hasn't been done through SetValue().
 * Saving the status will be dealt with by the XAJAX function.
 */
function chamilo_void_save_asset(score, max, min, status) {
    logit_lms('chamilo_void_save_asset('+score+','+max+','+min+','+status+')', 3);
    olms.score = score;
    if ((max == null) || (max == '')){
        max = 100;
    }

    if ((min == null) || (min == '')){
        min = 0;
    }

    // Assume a default of 100, otherwise the score will not get saved (see lpi->set_score())
    xajax_save_item(
        olms.lms_lp_id,
        olms.lms_user_id,
        olms.lms_view_id,
        olms.lms_item_id,
        score,
        max,
        min,
        status
    );
}

/**
 * Logs information about SCORM messages into the log frame
 * @param	string	Message to log
 * @param	integer Priority (0 for top priority, 3 for lowest)
 */
function logit_scorm(message, priority) {
    if (scorm_logs) {
        log_in_log("SCORM: " + message, priority);
    }
    return false;
}

function log_in_log(message, priority)
{
    // Colorize a little
    var color = "color: black";
    switch (priority) {
        case 0:
            color = "color:red;font-weight:bold";
            break;
        case 1:
            color = "color:orange";
            break;
        case 2:
            color = "color:green";
            break;
        case 3:
            color = "color:blue";
        break;
    }

    if (this.console) {
        // Log in console with syntax colouring
        console.log("%c"+message, color);
    } else {
        window.console.log(message);
    }
}

/**
 * Logs information about LMS activity into the log frame
 * @param	string	Message to log
 * @param	integer Priority (0 for top priority, 3 for lowest)
 */
function logit_lms(message, priority){
    if (scorm_logs) {
        log_in_log("LMS: " + message + ' (#lms_item_id = '+olms.lms_item_id+')', priority);
    }
    return false;
}

/**
 * Update the Table Of Contents frame, by changing CSS styles, mostly
 * @param	string	Action to be taken
 * @param	integer	Item id to update
 */
function update_toc(update_action, update_id, change_ids) {
    if (!change_ids || change_ids != 'no') {
        change_ids = 'yes';
    }
    var myelem = $("#toc_"+update_id);
    logit_lms('update_toc("'+update_action+'", '+update_id+')',2);

    if (update_id != 0) {
        // Switch function is broken
        if (update_action == "unhighlight" || update_action == "highlight") {
            if (update_action == "unhighlight") {
                myelem.removeClass('scorm_highlight');
            } else {
                if (change_ids=='yes') {
                   olms.lms_next_item = update_id;
                   olms.lms_previous_item = update_id;
                }
                myelem.addClass('scorm_highlight');
            }
        } else {
            myelem.removeClass("scorm_not_attempted scorm_incomplete scorm_completed scorm_failed scorm_passed");
            if (update_action == "not attempted") {
                myelem.addClass('scorm_not_attempted');
            } else if (update_action == "incomplete") {
                myelem.addClass('scorm_not_attempted');
            } else if (update_action == "completed") {
                myelem.addClass('scorm_completed');
            } else if (update_action == "failed") {
                myelem.addClass('scorm_failed');
            } else if (update_action == "passed") {
                myelem.addClass('scorm_completed');
            } else if (update_action == "browsed") {
                myelem.addClass('scorm_completed');
            } else {
                logit_lms('Update action unknown',1);
            }
        }
    }
    return true;
}

/**
 * Update the stats frame using a reload of the frame to avoid unsynched data
 */
function update_stats() {
    logit_lms('update_stats()');
    if (olms.execute_stats) {
        try {
            cont_f = document.getElementById('content_id');
            cont_f.src = statsUrl;
            cont_f.reload();
        } catch (e) {
            return false;
        }
    }
    olms.execute_stats = false;
}

/**
 * Updates the progress bar with the new status. Prevents the need of a page refresh and flickering
 * @param	integer	Number of completed items
 * @param	integer	Number of items in total
 * @param	string  Display mode (absolute 'abs' or percentage '%').Defaults to %
 */
function update_progress_bar(nbr_complete, nbr_total, mode) {
    logit_lms('update_progress_bar('+nbr_complete+', '+nbr_total+', '+mode+')',3);
    logit_lms(
        'update_progress_bar with params: lms_lp_id= ' + olms.lms_lp_id +
        ', lms_view_id= '+ olms.lms_view_id + ' lms_user_id= '+ olms.lms_user_id,
        3
    );

    if (mode == '') {
        mode='%';
    }

    if (nbr_total == 0) {
        nbr_total=1;
    }

    var percentage = (nbr_complete/nbr_total)*100;
    percentage = Math.round(percentage);

    var progress_bar = $("#progress_bar_value");
    progress_bar.css('width', percentage + "%");

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
    progress_bar.html(mytext);
    return true;
}

/**
 * Update the gamification values (number of stars and score)
 */
function updateGamificationValues()
{
    var fetchValues = $.ajax(
        '<?php echo api_get_path(WEB_AJAX_PATH); ?>lp.ajax.php' + courseUrl,
        {
        dataType: 'json',
        data: {
            a: 'update_gamification'
        }
    });

    $.when(fetchValues).done(function (values) {
        if (values.stars > 0) {
            $('#scorm-gamification .fa-star:nth-child(' + values.stars + ')').addClass('level');
        }

        $('#scorm-gamification .col-xs-4').text(values.score);
    });
}

/**
 * Analyses the variables that have been modified through this SCO's life and
 * put them into an array for later shipping to lp_ajax_save_item.php
 * @return  array   Array of SCO variables
 */
function process_scorm_values() {
    logit_scorm('process_scorm_values()');
    for (i=0; i < olms.scorm_variables.length; i++) {
        if (olms.updatable_vars_list[olms.scorm_variables[i]]) {
            olms.variable_to_send.push(olms.scorm_variables[i]);
        }
    }
    return olms.variable_to_send;
}

/**
 * Reinitializes the SCO's modified variables to an empty list.
 * @return  void
 * @uses The global updatable_vars_list array to register this
 */
function reinit_updatable_vars_list() {
    logit_scorm('Cleaning updatable_vars_list: reinit_updatable_vars_list');
    logit_scorm('Original status: ' + olms.lesson_status);

    var defaultStatus = 'not attempted';
    if (olms.updatable_vars_list['cmi.core.lesson_status']) {
        if (olms.lesson_status != '') {
            defaultStatus = olms.lesson_status;
        }
    }

    for (i=0;i < olms.scorm_variables.length;i++) {
        if (olms.updatable_vars_list[olms.scorm_variables[i]]) {
            olms.updatable_vars_list[olms.scorm_variables[i]]=false;
        }
    }

    logit_lms('Status after reinit: ' + defaultStatus, 3);
    olms.lesson_status = defaultStatus;
}

/**
 * Function that handles the saving of an item and switching from an item to another.
 * Once called, this function should be able to do the whole process of
 * (1) saving the current item,
 * (2) refresh all the values inside the SCORM API object,
 * (3) open the new item into the content_id frame,
 * (4) refresh the table of contents
 * (5) refresh the progress bar (completion)
 * (6) refresh the message frame
 * @param	integer		Chamilo ID for the current item
 * @param	string		This parameter can be a string specifying the next
 *						item (like 'next', 'previous', 'first' or 'last') or the id to the next item
 */
function switch_item(current_item, next_item)
{
    logit_lms('switch_item() called with params '+olms.lms_item_id+' and '+next_item+'',2);

    if (olms.lms_initialized == 0) {
        // Fix error when flash is not loaded and SCO is not started BT#14944
        olms.G_LastError = G_NotInitialized;
        olms.G_LastErrorMessage = G_NotInitializedMessage;
        logit_scorm('Error '+ G_NotInitialized + G_NotInitializedMessage, 0);
        //window.location.reload(false);

        var url = window.location.href + '&item_id='  + parseInt(next_item);
        window.location.replace(url);

        return false;
    }

    olms.switch_finished = 0; //only changed back once LMSInitialize() happens

    // backup these params
    var orig_current_item   = current_item;
    var orig_next_item      = next_item;
    var orig_lesson_status  = olms.lesson_status;
    var orig_item_type      = olms.lms_item_types['i'+current_item];
    var next_item_type      = olms.lms_item_types['i'+next_item];
    if (olms.statusSignalReceived == 0 && olms.lesson_status != 'not attempted') {
        // In this situation, the status can be considered set as it was clearly
        // set in a previous stage
        olms.statusSignalReceived = 1;
    }

    /*
     There are four "cases" for switching items:
     (1) asset switching to asset
         We need to save, then switch
     (2) asset switching to sco
         We need to save, switching not necessary (LMSInitialize does the job)
     (3) sco switching to asset
         We need to switch the document in the content frame, but we cannot
         switch the item details, otherwise the LMSFinish() call (that *must*
         be triggered by the SCO when it unloads) will use bad values. However,
         we need to load the new asset's context once the SCO has unloaded
     (4) sco switching to sco
         We don't need to switch nor commit, LMSFinish() on unload and
         LMSInitialize on load will do the job
     In any case, we need to change the current document frame.
     These cases, although clear here, are however very difficult to implement
    */

    if (orig_item_type != 'sco') {
        if (next_item_type != 'sco' ) {
            logit_lms('Case 1 - current != sco and next != sco');
        } else {
            logit_lms('Case 2 - current != sco but next == sco');
        }

        var saveAjax = xajax_save_item(
            olms.lms_lp_id,
            olms.lms_user_id,
            olms.lms_view_id,
            olms.lms_item_id,
            olms.score,
            olms.max,
            olms.min,
            olms.lesson_status,
            olms.asset_timer,
            olms.suspend_data,
            olms.lesson_location,
            olms.interactions,
            olms.lms_item_core_exit,
            orig_item_type,
            olms.session_id,
            olms.course_id,
            olms.finishSignalReceived,
            1,
            olms.statusSignalReceived
        );

        if (saveAjax) {
            $.when(saveAjax).done(function(results) {
                xajax_switch_item_details(
                    olms.lms_lp_id,
                    olms.lms_user_id,
                    olms.lms_view_id,
                    olms.lms_item_id,
                    next_item
                );
            });
        }
    } else {
        if (next_item_type != 'sco') {
            logit_lms('Case 3 - current == sco but next != sco');
        } else {
            logit_lms('Case 4 - current == sco and next == sco');
        }

        // Setting userNavigatesAway = 1
        var saveAjax = xajax_save_item_scorm(
            olms.lms_lp_id,
            olms.lms_user_id,
            olms.lms_view_id,
            olms.lms_item_id,
            olms.lms_session_id,
            olms.lms_course_id,
            olms.finishSignalReceived,
            1,
            olms.statusSignalReceived
        );

        if (saveAjax) {
            $.when(saveAjax).done(function(result) {
                reinit_updatable_vars_list();
                xajax_switch_item_toc(
                    olms.lms_lp_id,
                    olms.lms_user_id,
                    olms.lms_view_id,
                    olms.lms_item_id,
                    next_item
                );

                if (olms.item_objectives.length>0) {
                    xajax_save_objectives(
                        olms.lms_lp_id,
                        olms.lms_user_id,
                        olms.lms_view_id,
                        olms.lms_item_id,
                        olms.item_objectives
                    );
                }
            });
        }
    }

    /**
     * Because of SCORM 1.2's special rule about unsent commits and the fact
     * that a SCO should be SET TO 'completed' IF NO STATUS WAS SENT (and
     * then some checks have to be done on score), we have to force a
     * special commit here to avoid getting to the next element with a
     * missing prerequisite. The 'onunload' event is treated with
     * savedata_onunload(), and doesn't need to be triggered at any
     * particular time, but here we are in the case of switching to another
     * item, so this is particularly important to complete the element in
     * time.
     * However, this cannot be initiated from the JavaScript, mainly
     * because another onunload event can be triggered by the SCO itself,
     * which can set, for example, the status to incomplete while the
     * status has already been set to "completed" by the hand-made
     * savedata() (and then the status cannot be "incompleted"
     * anymore)
     */

    olms.execute_stats = false;

    // Considering info_lms_item[0] is initially the oldest and info_lms_item[1]
    // is the newest item, and considering we are done switching the items now,
    // we need to update these markers so that the new item is loaded when
    // changing the document in the content frame
    if (olms.info_lms_item[1]==next_item && next_item!='next' && next_item!='previous') {
        olms.info_lms_item[0]=next_item;
        olms.info_lms_item[1]=next_item;
    } else {
        if (next_item!='next' && next_item!='previous') {
            olms.info_lms_item[0]=olms.info_lms_item[1];
            olms.info_lms_item[1]=next_item;
        }
    }

    if (olms.info_lms_item[0]==next_item && next_item!='next' && next_item!='previous') {
        olms.info_lms_item[0]=next_item;
        olms.info_lms_item[1]=next_item;
    } else {
        if (next_item!='next' && next_item!='previous') {
            olms.info_lms_item[0]=olms.info_lms_item[0];
            olms.info_lms_item[1]=next_item;
        }
    }

    //(3) open the new item in the content_id frame
    switch (next_item) {
        case 'next':
            next_item = olms.lms_next_item;
            olms.info_lms_item[0] = olms.info_lms_item[1];
            olms.info_lms_item[1] = olms.lms_next_item;
            break;
        case 'previous':
            next_item = olms.lms_previous_item;
            olms.info_lms_item[0] = olms.info_lms_item[1];
            olms.info_lms_item[1] = olms.lms_previous_item;
            break;
        default:
            break;
    }

    var mysrc = '<?php echo api_get_path(WEB_CODE_PATH); ?>lp/lp_controller.php?action=content&lp_id=' + olms.lms_lp_id +
                '&item_id=' + next_item + '&cidReq=' + olms.lms_course_code + '&id_session=' + olms.lms_session_id;
    var cont_f = $("#content_id");

    <?php if ($oLP->mode == 'fullscreen') {
        ?>
        cont_f = window.open('' + mysrc, 'content_id', 'toolbar=0,location=0,status=0,scrollbars=1,resizable=1');
        cont_f.onload=function(){
            olms.info_lms_item[0]=olms.info_lms_item[1];
        }
        cont_f.onunload=function(){
            olms.info_lms_item[0]=olms.info_lms_item[1];
        }

    <?php
    } else {
        ?>
            log_in_log('loading '+mysrc+' in frame');
            cont_f.attr("src",mysrc);
    <?php
    } ?>

    if (olms.lms_item_type != 'sco') {
        xajax_start_timer();
    }

    // (4) refresh the audio player if needed
    $.ajax({
        type: "POST",
        url: "lp_nav.php"+courseUrl+ "&lp_id=" + olms.lms_lp_id,
        data: {
            lp_item: next_item
        },
        beforeSend: function() {
            $.each($('audio'), function () {
                if (this.player && this.player !== undefined) {
                    this.player.pause();
                }
            });
        },
        success: function(tmp_data) {
            if ($("#lp_media_file").length != 0) {
                $("#lp_media_file").html(tmp_data);
            }

            LPViewUtils.setHeightLPToc();
        }
    });

    loadForumThread(olms.lms_lp_id, next_item);
    checkCurrentItemPosition(olms.lms_item_id);

    return true;
}

/**
 * Hide or show the navigation buttons if the current item is the First or Last
 */
var checkCurrentItemPosition = function(lpItemId) {
    var currentItem = $.getJSON(
        '<?php echo api_get_path(WEB_AJAX_PATH); ?>lp.ajax.php' + courseUrl,
    {
        a: 'check_item_position',
        lp_item: lpItemId
    }
    ).done(function(parsedResponse,statusText,jqXhr) {
        var position = jqXhr.responseJSON;
        if (position == 'first') {
            $("#scorm-previous").hide();
            $("#scorm-next").show();
        } else if (position == 'none') {
            $("#scorm-previous").show();
            $("#scorm-next").show();
        } else if (position == 'last') {
            $("#scorm-previous").show();
            $("#scorm-next").hide();
        } else if (position == 'both') {
            $("#scorm-previous").hide();
            $("#scorm-next").hide();
        }
    });

}

/**
 * Get a forum info when the learning path item has a associated forum
 */
var loadForumThread = function(lpId, lpItemId) {
    var loadForum = $.getJSON(
        '<?php echo api_get_path(WEB_AJAX_PATH); ?>lp.ajax.php' + courseUrl, {
            a: 'get_forum_thread',
            lp: lpId,
            lp_item: lpItemId
        }
    );

    $.when(loadForum).done(function(forumThreadData) {
        var tabForumLink = $('.lp-view-tabs a[href="#lp-view-forum"]'),
            tabForum = tabForumLink.parent();
            $("#navTabs").show();
            $("#tab-iframe").removeClass("tab-none-forum");
            $("#btn-menu-float").removeClass("none-forum");

        if (forumThreadData.error) {
            tabForumLink.removeAttr('data-toggle');
            tabForum.addClass('disabled');
            $("#navTabs").hide();
            $("#tab-iframe").addClass("tab-none-forum");
            $("#btn-menu-float").addClass("none-forum");
            $('#lp-view-forum').html('');

            return;
        }

        tabForumLink.attr('data-toggle', 'tab');
        tabForum.removeClass('disabled');

        var forumIframe = $('<iframe>').attr({
            width:'100%',
            frameborder:'0',
            scrolling:'yes',
            tabindex:'0',
            id:'chamilo-disqus',
            src: '<?php echo api_get_path(WEB_CODE_PATH); ?>forum/viewthread.php?<?php echo api_get_cidreq(); ?>&' + $.param({
                gradebook: 0,
                origin: 'learnpath',
                forum: forumThreadData.forumId,
                thread: forumThreadData.threadId,
                posts_order: 'desc'
            })
        });

        $('#lp-view-forum').html(forumIframe);
    });

};

/**
 * Save a specific item (with its interactions, if any) into the LMS through
 * an AJAX call to lp_ajax_save_item.php.
 * Because of the need to pass an array, we have to build the parameters
 * manually into GET[].
 * This function has a twin sister for SCO elements (xajax_save_item_scorm)
 * which takes into account the interactions.
 * @param   int     ID of the learning path (for the LMS)
 * @param   int     ID of the user
 * @param   int     ID of the view of this learning path
 * @param   int     ID of the item currently looked at
 * @param   float   Score
 * @param   float   Max score
 * @param   float   Min score
 * @param   string  Lesson status
 * @param   string  Current session time (in 'xxxx:xx:xx.xx' format)
 * @param   string  Suspend data (maximum 255 chars)
 * @param   string  Lesson location (which page we've reached in the SCO)
 * @param   array   Interactions
 * @param   string  Core exit value (up to 4096 chars)
 * @return  void
 * @uses lp_ajax_save_item.php through an AJAX call
 */
function xajax_save_item(
    lms_lp_id,
    lms_user_id,
    lms_view_id,
    lms_item_id,
    score,
    max,
    min,
    lesson_status,
    session_time,
    suspend_data,
    lesson_location,
    interactions,
    lms_item_core_exit,
    item_type,
    session_id,
    course_id,
    finishSignalReceived,
    userNavigatesAway,
    statusSignalReceived
) {
    var params = '';
    if (typeof(finishSignalReceived) == 'undefined') {
        finishSignalReceived = 0;
    }

    if (typeof(userNavigatesAway) == 'undefined') {
        userNavigatesAway = 0;
    }

    if (typeof(statusSignalReceived) == 'undefined') {
        statusSignalReceived = 0;
    }

    params += 'lid='+lms_lp_id+'&uid='+lms_user_id+'&vid='+lms_view_id;
    params += '&iid='+lms_item_id+'&s='+score+'&max='+max+'&min='+min;
    params += '&status='+lesson_status+'&t='+session_time;
    params += '&suspend='+suspend_data+'&loc='+lesson_location;
    params += '&core_exit='+lms_item_core_exit;
    params += '&session_id='+session_id;
    params += '&course_id='+course_id;
    params += '&finishSignalReceived='+finishSignalReceived;
    params += '&userNavigatesAway='+userNavigatesAway;
    params += '&statusSignalReceived='+statusSignalReceived;

    if (item_type != 'sco') {
        logit_lms('xajax_save_item with params:' + params, 3);
        return $.ajax({
            type:"POST",
            data: params,
            url: "lp_ajax_save_item.php" + courseUrl,
            dataType: "script",
            async: false
        });
    }

    return false;
}

/**
 * Save a SCORM item's variables, getting its SCORM values from
 * updatable_vars_list. Takes interactions into account and considers whether
 * variables have been modified or not.
 * @param   int     ID of the learning path
 * @param   int     ID of the user
 * @param   int     ID of the view
 * @param   int     ID of the item
 * @param   bool    1 if this call comes from a "LMSFinish()" call, 0 or nothing otherwise
 * @return void
 * @uses olms.updatable_vars_list
 * @uses lp_ajax_save_item.php through an AJAX call
 */
function xajax_save_item_scorm(
    lms_lp_id,
    lms_user_id,
    lms_view_id,
    lms_item_id,
    session_id,
    course_id,
    finishSignalReceived,
    userNavigatesAway,
    statusSignalReceived,
    useSendBeacon,
    forceIframeSave
) {
    if (typeof(finishSignalReceived) == 'undefined') {
        finishSignalReceived = 0;
    }
    if (typeof(userNavigatesAway) == 'undefined') {
        userNavigatesAway = 0;
    }

    if (typeof(statusSignalReceived) == 'undefined') {
        statusSignalReceived = 0;
    }

    if (typeof(forceIframeSave) == 'undefined') {
        forceIframeSave = 0;
    }

    var is_interactions='false';
    var params = 'lid='+lms_lp_id+'&uid='+lms_user_id+'&vid='+lms_view_id+'&iid='+lms_item_id;
    // The missing arguments will be ignored by lp_ajax_save_item.php
    //params += '&s=&max=&min=&status=&t=&suspend=&loc=&interact=&core_exit=';
    params += '&session_id='+session_id;
    params += '&course_id='+course_id;
    params += '&finishSignalReceived='+finishSignalReceived;
    params += '&userNavigatesAway='+userNavigatesAway;
    params += '&statusSignalReceived='+statusSignalReceived;
    params += '&forceIframeSave='+forceIframeSave;

    var my_scorm_values = new Array();
    my_scorm_values = process_scorm_values();
    for (k=0; k < my_scorm_values.length; k++) {
        if (my_scorm_values[k]=='cmi.core.session_time') {
            params += '&t='+olms.session_time;
        } else if (my_scorm_values[k]=='cmi.core.lesson_status' && olms.lesson_status!='') {
             params += '&status='+olms.lesson_status;
        } else if (my_scorm_values[k]=='cmi.core.score.raw') {
             params += '&s='+olms.score;
        } else if (my_scorm_values[k]=='cmi.core.score.max') {
            params += '&max='+olms.max;
        } else if (my_scorm_values[k]=='cmi.core.score.min') {
            params += '&min='+olms.min;
        } else if (my_scorm_values[k]=='cmi.core.lesson_location') {
            params += '&loc='+olms.lesson_location;
        } else if (my_scorm_values[k]=='cmi.completion_status') {
        } else if (my_scorm_values[k]=='cmi.score.scaled') {
        } else if (my_scorm_values[k]=='cmi.suspend_data') {
            // params += '&suspend='+olms.suspend_data;
            // Fixes error when scorm sends text with "+" sign
            params += '&suspend='+encodeURIComponent(olms.suspend_data);
        } else if (my_scorm_values[k]=='cmi.completion_status') {
        } else if (my_scorm_values[k]=='cmi.core.exit') {
            params += '&core_exit='+olms.lms_item_core_exit;
        }
        if (my_scorm_values[k]=='interactions') {
            is_interactions='true';
        } else {
            is_interactions='false';
        }
    }

    if (is_interactions == 'true')  {
        interact_string = '';
        temp = '';
        for (i in olms.interactions) {
            interact_string += '&interact['+i+']=';
            interact_temp = '[';
            for (j in olms.interactions[i]) {
                temp = olms.interactions[i][j];
                temp = ''+temp; // if temp == 1 there are problems with indexOf and an integer number
                //this fix when an interaction have ',' i.e:   {a,b,c,d} is replace to {a@.|@b@.|@c@.|@d} see DT#4444
                while(temp.indexOf(',') >= 0){
                    temp = temp.replace(',','@.|@');
                };
                interact_temp +=temp+',';
            }
            interact_temp = interact_temp.substr(0,(interact_temp.length-2)) + ']';
            //  interact_string += encodeURIComponent(interact_temp);
            interact_string += interact_temp;
        }
        //interact_string = encodeURIComponent(interact_string.substr(0,(interact_string.length-1)));
        params += interact_string;
        is_interactions='false';
    }

    logit_lms('xajax_save_item_scorm with params:' + params, 3);
    var codePathUrl = '<?php echo api_get_path(WEB_CODE_PATH).'lp/'; ?>';
    var saveUrl = codePathUrl + "lp_ajax_save_item.php" + courseUrl;

    if (useSendBeacon == 1 && navigator.sendBeacon) {
        console.log('useSendBeacon');
        var formData = new FormData();
        var paramsToArray = params.split('&');
        for (var i = 0; i < paramsToArray.length; i++) {
            if (!paramsToArray[i])
                continue;
            var pair = paramsToArray[i].split('=');
            formData.append(pair[0], decodeURIComponent(pair[1]));
        }

        result = navigator.sendBeacon(saveUrl, formData);
        console.log(result);

        params = '';
        my_scorm_values = null;

        return false;
    } else {
        logit_lms('Ajax call');
        var ajax = $.ajax({
            type:"POST",
            data: params,
            url: saveUrl,
            dataType: "script",
            async: true
        });

        params = '';
        my_scorm_values = null;

        return ajax;
    }
}

/**
 * Starts the timer with the server clock time.
 * @return void
 * @todo check the timer stuff really works and rename function to startTimer()
 * @uses    lp_ajax_start_timer.php
 */
function xajax_start_timer() {
    logit_lms('xajax_start_timer() called',3);
    $.ajax({
        type: "GET",
        url: "lp_ajax_start_timer.php" + courseUrl,
        dataType: "script",
        async: false,
        success: function(time) {
            olms.asset_timer = time;
            olms.asset_timer_total = 0;
            logit_lms('xajax_start_timer result: ' + time,3);

            var date = new Date(time * 1000);
            logit_lms('xajax_start_timer result: ' + date.toString(),3);
        }
    });
}

/**
 * Save a specific item's objectives into the LMS through an Synch JAX call
 * @param   int     ID of the learning path
 * @param   int     ID of the user
 * @param   int     ID of the view
 * @param   int     ID of the item
 * @param   array   SCO's recorded objectives
 * @uses    lp_ajax_save_objectives.php
 */
function xajax_save_objectives(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,item_objectives) {
    var params = '';
    params += 'lid='+lms_lp_id+'&uid='+lms_user_id+'&vid='+lms_view_id;
    params += '&iid='+lms_item_id;
    obj_string = '';

    logit_lms('xajax_save_objectives with params:' + params, 3);

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
        url: "lp_ajax_save_objectives.php" + courseUrl,
        dataType: "script",
        async: false
    });
}

/**
 * Switch between two items through an AJAX call.
 * @param   int     ID of the learning path
 * @param   int     ID of the user
 * @param   int     ID of the view
 * @param   int     ID of the item
 * @param   int     ID of the next item
 * @uses    lp_ajax_switch_item.php
 */
function xajax_switch_item_details(lms_lp_id,lms_user_id,lms_view_id,lms_item_id,next_item) {
    var params = {
        'lid': lms_lp_id,
        'uid': lms_user_id,
        'vid': lms_view_id,
        'iid': lms_item_id,
        'next': next_item
    };

    logit_lms('xajax_switch_item_details with params:' + params, 3);

    return $.ajax({
        type: "POST",
        data: params,
        url: "lp_ajax_switch_item.php" + courseUrl,
        dataType: "script",
        async: true
    });
}

/**
 * Switch between two items through an AJAX call, but only update the TOC and
 * progress bar.
 * @param   int     ID of the learning path
 * @param   int     ID of the user
 * @param   int     ID of the view
 * @param   int     ID of the item
 * @param   int     ID of the next item
 * @uses    lp_ajax_switch_toc.php
 */
function xajax_switch_item_toc(lms_lp_id, lms_user_id, lms_view_id, lms_item_id, next_item) {
    var params = {
        'lid': lms_lp_id,
        'uid': lms_user_id,
        'vid': lms_view_id,
        'iid': lms_item_id,
        'next': next_item
    };
    logit_lms('xajax_switch_item_toc');

    $.ajax({
        type: "POST",
        data: params,
        url: "lp_ajax_switch_item_toc.php" + courseUrl,
        dataType: "script",
        async: false
    });
}

/**
 * Allow attach the glossary terms into html document of scorm. This has
 * nothing to do with SCORM itself, and should not interfere w/ SCORM either.
 * @param   string     automatic or manual values are allowed
*/
function attach_glossary_into_scorm(type) {
    var f = $('#content_id')[0];
    //Prevents "f is undefined" javascript error
    if (f == null) {
        logit_lms('attach_glossary_into_scorm failed', 0);
        return false;
    }

    try {
        var doc = f.contentWindow ? f.contentWindow.document : f.contentDocument ? f.contentDocument : f.document;
    } catch (ex) { }

    var $frame_content = $('body',doc);
    var my_text=$frame_content.html();

    my_protocol = location.protocol;
    my_pathname=location.pathname;
    work_path = my_pathname.substr(0,my_pathname.indexOf('<?php echo api_get_path(REL_COURSE_PATH); ?>'));
    var ajaxRequestUrl = '<?php echo api_get_path(WEB_CODE_PATH).'glossary/glossary_ajax_request.php'; ?>' + courseUrl;

    if (type == 'automatic') {
        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            beforeSend: function(object) {
            },
            type: "POST",
            url: ajaxRequestUrl,
            data: "glossary_data=true",
            success: function(datas) {
                if (datas.length==0) {
                    return false;
                }
                // glossary terms
                data_terms=datas.split("[|.|_|.|-|.|]");
                var complex_array = new Array();
                var cp_complex_array = new Array();
                for(i=0;i < data_terms.length;i++) {
                    specific_terms= data_terms[i].split("__|__|");
                    var real_term = specific_terms[1]; // glossary term
                    var real_code = specific_terms[0]; // glossary id
                    complex_array[real_code] = real_term;
                    cp_complex_array[real_code] = real_term;
                }

                complex_array.reverse();

                for (var my_index in complex_array) {
                    n = complex_array[my_index];
                    if (n == null) {
                        n = '';
                    } else {
                        for (var cp_my_index in cp_complex_array) {
                            cp_data = cp_complex_array[cp_my_index];
                            if (cp_data == null) {
                                cp_data = '';
                            } else {
                                if (cp_data == n) {
                                    my_index = cp_my_index;
                                }
                            }
                        }
                        //alert(n + ' ' + my_index);
                        $("iframe").contents().find('body').removeHighlight().highlight(n,my_index)
                    }
                }

                var complex_array = new Array();
                $("iframe").contents().find("body").on("click", ".glossary-ajax", function() {
                div_show_id="div_show_id";
                div_content_id="div_content_id";

                $("iframe").contents().find("body").
                    append('<div id="div_show_id"><div id="div_content_id">&nbsp;</div></div>');

                    show_dialog = $("iframe").contents().find("div#"+div_show_id);
                    show_description = $("iframe").contents().find("div#"+div_content_id);

                    var $target = $(this);

                    if ($("#learning_path_left_zone").is(':visible') ) {
                        var extra_left = $("#learning_path_left_zone").width() + 20;
                    } else {
                        var extra_left = 0;
                    }

                    show_dialog.dialog({
                        autoOpen: false,
                        width: 600,
                        height: 200,
                        position:  { my: 'left top', at: 'right top', of: $target, offset: extra_left+", 0"},
                        close: function(){
                             show_dialog.remove();
                             show_description.remove();
                        }
                    });
                    notebook_id=$(this).attr("name");
                    data_notebook=notebook_id.split("link");

                    my_glossary_id=data_notebook[1];
                    $.ajax({
                        contentType: "application/x-www-form-urlencoded",
                        type: "POST",
                        url: ajaxRequestUrl,
                        data: "glossary_id="+my_glossary_id,
                        success: function(data) {
                            show_description.html(data);
                            show_dialog.dialog("open");
                        }
                    });
                });
            }
        });
    } else {
        if (type == 'manual') {
            $("iframe").contents().find("body").on("click", ".glossary", function() {
                is_glossary_name = $(this).html();

                div_show_id="div_show_id";
                div_content_id="div_content_id";

                $("iframe").contents().find("body").
                    append('<div id="div_show_id"><div id="div_content_id">&nbsp;</div></div>');

                show_dialog = $("iframe").contents().find("div#"+div_show_id);
                show_description = $("iframe").contents().find("div#"+div_content_id);

                var $target = $(this);

                if ($("#learning_path_left_zone").is(':visible') ) {
                    var extra_left = $("#learning_path_left_zone").width() + 20;
                } else {
                    var extra_left = 0;
                }

                //$("#"+div_show_id).dialog("destroy");
                show_dialog.dialog({
                    autoOpen: false,
                    width: 600,
                    height: 200,
                    position:  { my: 'left top', at: 'right top', of: $target, offset: extra_left+", 0"},
                    close: function(){
                         show_dialog.remove();
                         show_description.remove();
                    }
                });

                $.ajax({
                    contentType: "application/x-www-form-urlencoded",
                    type: "POST",
                    url: ajaxRequestUrl,
                    data: "glossary_name="+is_glossary_name,
                    success: function(data) {
                         show_description.html(data);
                         show_dialog.dialog("open");
                    }
                });
            });
        }

        if (type == 'fix_links') {
            $(function() {
                var objects = $("iframe").contents().find('object');

                var pathname = location.pathname;
                var coursePath = pathname.substr(0, pathname.indexOf('/main/'));
                var url = "http://"+location.host + coursePath+"/courses/proxy.php?";

                objects.each(function (value, obj) {
                    var dialogId = this.id +'_dialog';
                    var openerId = this.id +'_opener';
                    var link = '<a id="'+openerId+'" href="#" class="generated btn">'+
                        '<div style="text-align: center"><img src="<?php echo Display::returnIconPath('play-circle-8x.png'); ?>"/><br />If video does not work, try clicking here.</div></a>';
                    var embed = $("iframe").contents().find("#"+this.id).find('embed').first();

                    var hasHttp = embed.attr('src').indexOf("http");

                    if (hasHttp < 0) {
                        return true;
                    }

                    var height = embed.attr('height');
                    var width = embed.attr('width');
                    var src = embed.attr('src').replace('https', 'http');

                    var completeUrl =  url + 'width='+embed.attr('width')+
                        '&height='+height+
                        '&id='+this.id+
                        '&flashvars='+encodeURIComponent(embed.attr('flashvars'))+
                        '&src='+src+
                        '&width='+width;

                    var iframe = '<iframe ' +
                        'style="border: 0px;"  width="100%" height="100%" ' +
                        'src="'+completeUrl+
                        '">' +
                        '</iframe>';

                    $("iframe").contents().find("#"+this.id).append(link + '<br />');
                    $("iframe").contents().find('#' + openerId).click(function() {
                        var w = window.open(completeUrl, "Video", "width="+width+", "+"height="+height+"");
                        w = window.document.title = 'Video';
                    });
                });

                var iframes = $("iframe").contents().find('iframe');

                iframes.each(function (value, obj) {
                    var randLetter = String.fromCharCode(65 + Math.floor(Math.random() * 26));
                    var uniqid = randLetter + Date.now();
                    var openerId = uniqid +'_opener';
                    var link = '<a id="'+openerId+'" class="generated" href="#">Open website <img width="16px" src="<?php echo Display::returnIconPath('link-external.png'); ?>"/></a>';
                    var embed = $(this);
                    var height = embed.attr('height');
                    var width = embed.attr('width');
                    var src = embed.attr('src');
                    var completeUrl =  url + 'width='+embed.attr('width')+
                        '&height='+height+
                        '&type=iframe'+
                        '&id='+uniqid+
                        '&src='+src+
                        '&width='+width;
                    var result = $("iframe").contents().find('#'+openerId);

                    var n = src.indexOf("youtube.com");
                    if (n > 0) {
                        return true;
                    }

                    if (result.length == 0) {
                        if (embed.prev().attr('class') != 'generated') {
                            $(this).parent().prepend(link + '<br />');
                            $("iframe").contents().find('#' + openerId).click(function() {
                                width = 1280;
                                height = 640;
                                var win = window.open(completeUrl, "Video", "width=" + width + ", " + "height=" + height + "");
                                win.document.title = 'Video';
                            });
                        }
                    }
                });

                var anchors = $("iframe").contents().find('a').not('.generated');
                anchors.each(function (value, obj) {
                    if ($(this).next().attr('class') != 'generated') {
                        var content = $(this).html();
                        content = content.replace('<br />', '');
                        content = content.replace('<br>', '');
                        content = $.trim(content);
                        if (content == '') {
                            return true;
                        }

                        if ($(this).attr('href')) {
                            var hasLocalhost = $(this).attr('href').indexOf(location.host);
                            if (hasLocalhost > 0) {
                                return true;
                            }

                            var hasJs = $(this).attr('href').indexOf('javascript');
                            if (hasJs >= 0) {
                                return true;
                            }
                        }

                        if ($(this).attr('class')) {
                            var hasAccordion = $(this).attr('class').indexOf('accordion-toggle');
                            if (hasAccordion >= 0) {
                                return true;
                            }
                        }

                        var src = $(this).attr('href');
                        src = url+'&type=link&src='+src;
                        src = src.replace('https', 'http');
                        $(this).attr('href', src);
                        $(this).attr('target', '_blank');
                        var myAnchor = $('<a><img width="16px" src="<?php echo Display::returnIconPath('link-external.png'); ?>"/></a>').attr("href", src).attr('target', '_blank').attr('class', 'generated');
                        $(this).after(myAnchor);
                        $(this).after('-');
                    }
                });
            });
        }
    }
}

/**
 * Updates the time bar with the new status. Prevents the need of a page refresh and flickering
 * @param	integer	Number of completed items
 * @param	integer	Number of items in total
 * @param	string  Display mode (absolute 'abs' or percentage '%').Defaults to %
 */
function update_time_bar(nbr_complete, nbr_total, mode)
{
    logit_lms('update_time_bar('+nbr_complete+', '+nbr_total+', '+mode+')',3);
    logit_lms(
        'update_time_bar with params: lms_lp_id= ' + olms.lms_lp_id +
        ', lms_view_id= '+ olms.lms_view_id + ' lms_user_id= '+ olms.lms_user_id,
        3
    );

    if (mode == '') {
        mode='%';
    }

    if (nbr_total == 0) {
        nbr_total=1;
    }

    var percentage = (nbr_complete/nbr_total)*100;
    percentage = Math.round(percentage);

    var progress_bar = $("#progress_bar_value2");
    progress_bar.css('width', percentage + "%");

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
    progress_bar.html(mytext);
    return true;
}

/**
 * Update chronometer
 */
function update_chronometer(text_hour, text_minute, text_second)
{
    $("#hour").text(text_hour);
    $("#minute").text(text_minute);
    $("#second").text(text_second);

    var timerData = {
        hour: parseInt($("#hour").text()),
        minute: parseInt($("#minute").text()),
        second:  parseInt($("#second").text())
    };
    /*
    var timerData = {
       hour: text_hour,
       minute: text_minute,
       second: text_second
   };
   */

    //window.timerInterval = null;
    clearInterval(window.timerInterval);
    window.timerInterval = setInterval(function(){
        // Seconds
        timerData.second++;
        if(timerData.second >= 60) {
            timerData.second = 0;
            timerData.minute++;
        }

        // Minutes
        if(timerData.minute >= 60) {
            timerData.minute = 0;
            timerData.hour++;
        }

        $("#hour").text(timerData.hour < 10 ? '0' + timerData.hour : timerData.hour);
        //$("#hour").text(timerData.hour);
        $("#minute").text(timerData.minute < 10 ? '0' + timerData.minute : timerData.minute);
        //$("#minute").text(timerData.minute);
        $("#second").text(timerData.second < 10 ? '0' + timerData.second : timerData.second);
        //$("#second").text(timerData.second);
    }, 1000);

    return true;
}

/**
 * Get the locally stored suspend data
 * See suspend_data case in LMSGetValue()
 */
function get_local_suspend_data()
{
    var final_suspend_data = olms.suspend_data;
    var idSuspendData = olms.lms_item_id + 'suspenddata' +  olms.lms_view_id + 'u' + olms.lms_user_id;
    try{
        if (localStorage) {
            mem_suspend_data = window.localStorage.getItem(idSuspendData);
            if (mem_suspend_data === null || mem_suspend_data == "null"){
                mem_suspend_data = "";
            }
            if (mem_suspend_data === undefined) {
                mem_suspend_data = "";
            }
            if (typeof mem_suspend_data == 'undefined') {
                mem_suspend_data = "";
            }
            if (mem_suspend_data != ""){
                if (olms.suspend_data.indexOf("ICPLAYER_") != -1 || mem_suspend_data.indexOf("ICPLAYER_") != -1) {
                    final_suspend_data = mem_suspend_data;
                }
            }
        }
    } catch(err) {

    }
    return final_suspend_data;
}

/**
 * Save suspend_data in localStorage
 * See suspend_data case in LMSSetValue()
 */
function save_suspend_data_in_local()
{
    if (localStorage) {
        if (olms.suspend_data) {
            var suspend_data_local = olms.suspend_data;
            if (suspend_data_local === null||suspend_data_local == "null"){
                suspend_data_local = "";
            }
            if (suspend_data_local === undefined) {
                suspend_data_local = "";
            }
            if (typeof suspend_data_local == 'undefined') {
                suspend_data_local = "";
            }
            if (suspend_data_local.indexOf("ICPLAYER_")!=-1) {
                var idSuspendData = olms.lms_item_id + 'suspenddata' +  olms.lms_view_id + 'u' + olms.lms_user_id;
                try {
                    window.localStorage.setItem(idSuspendData,suspend_data_local);
                } catch(err) {

                }
            }
        }
    }
}

/**
* It launchs results for lti provider
*/
function sendLtiLaunch(ltiLaunchId, lpId)
{
    var url = "<?php echo api_get_path(WEB_PLUGIN_PATH).'lti_provider/tool/api/score.php?'.api_get_cidreq(); ?>&lti_tool=lp&launch_id="+ltiLaunchId+"&lti_result_id="+lpId;
    $.get(url);
}

