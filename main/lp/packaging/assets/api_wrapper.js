/**
 * Wrapper to the SCORM API provided by Chamilo
 * The complete set of functions and variables are in this file to avoid unnecessary file
 * accesses.
 * Only event triggers and answer data are inserted into the final document.
 * @author	Yannick Warnier  - inspired by the ADLNet documentation on SCORM content-side API
 * @package scorm.js
 */

/**
 * Initialisation of the SCORM API section.
 * Find the SCO functions (startTimer, computeTime, etc in the second section)
 * Find the Chamilo-proper functions (checkAnswers, etc in the third section)
 */

var _debug = true;
var findAPITries = 0;
var _apiHandle = null; //private variable
var errMsgLocate = "Unable to locate the LMS's API implementation";
var _NoError = 0;
var _GeneralException = 101;
var _ServerBusy = 102;
var _InvalidArgumentError = 201;
var _ElementCannotHaveChildren = 202;
var _ElementIsNotAnArray = 203;
var _NotInitialized = 301;
var _NotImplementedError = 401;
var _InvalidSetValue = 402;
var _ElementIsReadOnly = 403;
var _ElementIsWriteOnly = 404;
var _IncorrectDataType = 405;
var startTime;
var exitPageStatus;

/**
 * Gets the API handle right into the local API object and ensure there is only one.
 * Using the singleton pattern to ensure there's only one API object.
 * @return	object The API object as given by the LMS
 */
var API = new function()
{
    if (_apiHandle == null) {
        _apiHandle = getAPI();
    }

    return _apiHandle;
}

/**
 * Finds the API on the LMS side or gives up giving an error message
 * @param	object	The window/frame object in which we are searching for the SCORM API
 * @return	object	The API object recovered from the LMS's implementation of the SCORM API
 */
function findAPI(win)
{
    while((win.API == null) && (win.parent != null) && (win.parent != win)) {
        findAPITries++;
        if (findAPITries>10) {
            alert("Error finding API - too deeply nested");
            return null;
        }
        win = win.parent
    }
    return win.API;
}

/**
 * Gets the API from the current window/frame or from parent objects if not found
 * @return	object	The API object recovered from the LMS's implementation of the SCORM API
 */
function getAPI()
{
    //window is the global/root object of the current window/frame
    var MyAPI = findAPI(window);
    //look through parents if any
    if ((MyAPI == null) && (window.opener != null) && (typeof(window.opener) != "undefined")) {
        MyAPI = findAPI(window.opener);
    }
    //still not found? error message
    if (MyAPI == null) {
        alert("Unable to find SCORM API adapter.\nPlease check your LMS is considering this page as SCORM and providing the right JavaScript interface.")
    }
    return MyAPI;
}

/**
 * Handles error codes (prints the error if it has a description)
 * @return	int	Error code from LMS's API
 */
function errorHandler()
{
    if (API == null) {
        alert("Unable to locate the LMS's API. Cannot determine LMS error code");
        return;
    }
    var errCode = API.LMSGetLastError().toString();
    if (errCode != _NoError) {
        if (errCode == _NotImplementedError) {
            var errDescription = "The LMS doesn't support this feature";
            if (_debug) {
                errDescription += "\n";
                errDescription += api.LMSGetDiagnostic(null);
            }
            addDebug(errDescription);
        } else {
            var errDescription = API.LMSGetErrorString(errCode);
            if (_debug) {
                errDescription += "\n";
                errDescription += api.LMSGetDiagnostic(null);
            }
            addDebug(errDescription);
        }
    }
    return errCode;
}

function addDebug(message) {
    if (_debug && window.console) {
        console.log(message);
    }
}

function addDebugTable(message) {
    if (_debug && window.console) {
        console.table(message);
    }
}

/**
 * Calls the LMSInitialize method of the LMS's API object
 * @return string	The string value of the LMS returned value or false if error (should be "true" otherwise)
 */
function doLMSInitialize()
{
    if (API == null) {
        alert(errMsgLocate + "\nLMSInitialize failed");
        return false;
    }

    var result = API.LMSInitialize("");
    if (result.toString() != "true") {
        var err = errorHandler();
    }
    return result.toString();
}

/**
 * Calls the LMSFinish method of the LMS's API object
 * @return	string	The string value of the LMS return value, or false if error (should be "true" otherwise)
 */
function doLMSFinish()
{
    if (API == null) {
        alert(errMsgLocate + "\nLMSFinish failed");
        return false;
    } else {
        var result = API.LMSFinish('');
        if (result.toString() != "true") {
            var err = errorHandler();
        }
    }
    return result.toString();
}
/**
 * Calls the LMSGetValue method
 * @param	string	The name of the SCORM parameter to get
 * @return	string	The value returned by the LMS
 */
function doLMSGetValue(name)
{
    if (API == null) {
        alert(errMsgLocate + "\nLMSGetValue was not successful.");
        return '';
    } else {
        var value = API.LMSGetValue(name);
        var errCode = API.LMSGetLastError().toString();
        if (errCode != _NoError) {
            // an error was encountered so display the error description
            var errDescription = API.LMSGetErrorString(errCode);
            addDebug("LMSGetValue(" + name + ") failed. \n" + errDescription)
            return '';
        }

        return value.toString();
    }
}

/**
 * Calls the LMSSetValue method of the API object
 * @param	string	The name of the SCORM parameter to set
 * @param	string	The value to set the parameter to
 * @return  void
 */
function doLMSSetValue(name, value)
{
    if (API == null) {
        alert("Unable to locate the LMS's API Implementation.\nLMSSetValue was not successful.");
        return;
    } else {
        var result = API.LMSSetValue(name, value);
        if (result.toString() != "true") {
            var err = errorHandler();
        }
    }
    return;
}

/**
 * Calls the LMSCommit method
 */
function doLMSCommit()
{
    if (API == null) {
        alert(errMsgLocate + "\nLMSCommit was not successful.");
        return "false";
    } else {
        var result = API.LMSCommit("");
        if (result != "true") {
            var err = errorHandler();
        }
    }
    return result.toString();
}

/**
 * Calls GetLastError()
 */
function doLMSGetLastError()
{
    if (API == null) {
        alert(errMsgLocate + "\nLMSGetLastError was not successful.");
        //since we can't get the error code from the LMS, return a general error
        return _GeneralError;
    }
    return API.LMSGetLastError().toString();
}

/**
 * Calls LMSGetErrorString()
 */
function doLMSGetErrorString(errorCode)
{
    if (API == null) {
        alert(errMsgLocate + "\nLMSGetErrorString was not successful.");
    }

    return API.LMSGetErrorString(errorCode).toString();
}

/**
 * Calls LMSGetDiagnostic()
 */
function doLMSGetDiagnostic(errorCode)
{
    if (API == null) {
        alert(errMsgLocate + "\nLMSGetDiagnostic was not successful.");
    }

    return API.LMSGetDiagnostic(errorCode).toString();
}

/**
 * Initialise page values
 */
function loadPage()
{
    var result = doLMSInitialize();
    if (result) {
        var status = doLMSGetValue("cmi.core.lesson_status");
        if (status == "not attempted") {
            doLMSSetValue("cmi.core.lesson_status", "incomplete");
        }
        exitPageStatus = false;
        startTimer();
    }
}

/**
 * Starts the local timer
 */
function startTimer()
{
    startTime = new Date().getTime();
}

/**
 * Calculates the total time and sends the result to the LMS
 */
function computeTime()
{
    if (startTime != 0) {
        var currentDate = new Date().getTime();
        var elapsedSeconds = ( (currentDate - startTime) / 1000 );
        var formattedTime = convertTotalSeconds(elapsedSeconds);
    } else {
        formattedTime = "00:00:00.0";
    }

    doLMSSetValue( "cmi.core.session_time", formattedTime );
}
/**
 * Formats the time in a SCORM time format
 */
function convertTotalSeconds(ts)
{
    var sec = (ts % 60);
    ts -= sec;
    var tmp = (ts % 3600);  //# of seconds in the total # of minutes
    ts -= tmp;              //# of seconds in the total # of hours

    // convert seconds to conform to CMITimespan type (e.g. SS.00)
    sec = Math.round(sec*100)/100;
    var strSec = new String(sec);
    var strWholeSec = strSec;
    var strFractionSec = "";

    if (strSec.indexOf(".") != -1) {
        strWholeSec = strSec.substring(0, strSec.indexOf("."));
        strFractionSec = strSec.substring(strSec.indexOf(".") + 1, strSec.length);
    }
    if (strWholeSec.length < 2) {
        strWholeSec = "0" + strWholeSec;
    }
    strSec = strWholeSec;
    if (strFractionSec.length) {
        strSec = strSec + "." + strFractionSec;
    }
    if ((ts % 3600) != 0)
        var hour = 0;
    else var hour = (ts / 3600);
    if ((tmp % 60) != 0)
        var min = 0;
    else var min = (tmp / 60);
    if ((new String(hour)).length < 2)
        hour = "0" + hour;
    if ((new String(min)).length < 2)
        min = "0" + min;
    var rtnVal = hour + ":" + min + ":" + strSec;
    return rtnVal
}
/**
 * Handles the use of the back button (saves data and closes SCO)
 */
function doBack()
{
    checkAnswers(true);
    doLMSSetValue( "cmi.core.exit", "suspend" );
    computeTime();
    exitPageStatus = true;
    var result;
    result = doLMSCommit();
    result = doLMSFinish();
}

/**
 * Handles the closure of the current SCO before an interruption. This is only useful if the LMS
 * deals with the cmi.core.exit, cmi.core.lesson_status and cmi.core.lesson_mode *and* the SCO
 * sends some kind of value for cmi.core.exit, which is not the case here (yet).
 */
function doContinue(status)
{
    // Reinitialize Exit to blank
    doLMSSetValue( "cmi.core.exit", "" );
    var mode = doLMSGetValue( "cmi.core.lesson_mode" );
    if ( mode != "review"  &&  mode != "browse" )
    {
        doLMSSetValue( "cmi.core.lesson_status", status );
    }
    computeTime();
    exitPageStatus = true;
    var result;
    result = doLMSCommit();
    result = doLMSFinish();
}

/**
 * handles the recording of everything on a normal shutdown
 */
function doQuit()
{
    checkAnswers();
    computeTime();
    exitPageStatus = true;
    var result;
    result = doLMSCommit();
    result = doLMSFinish();
}

/**
 * Called upon unload event from body element
 */
function unloadPage(status)
{
    if (!exitPageStatus)
    {
        // doQuit( status );
    }
}

/**
 * Checks the answers on the test formular page
 */
function checkAnswers(interrupted)
{
    var tmpScore = 0;
    var status = 'not attempted';
    var scoreMax = 0;

    addDebug('Number of questions: '+ questions.length);

    for (var i=0; i < questions.length; i++) {
        if (questions[i] != undefined && questions[i] != null) {
            var idQuestion = questions[i];
            var type = questions_types[idQuestion];
            var interactionScore = 0;
            var interactionAnswers = '';
            var interactionCorrectResponses = '';
            var interactionType = '';

            addDebug('idQuestion: ' +idQuestion + ', Type: ' +type);
            addDebug('questions_answers: ');
            addDebugTable(questions_answers[idQuestion]);
            addDebug('questions_answers_ponderation: ');
            addDebugTable(questions_answers_ponderation[idQuestion]);
            addDebug('questions_answers_correct: ');
            addDebugTable(questions_answers_correct[idQuestion]);

            switch (type) {
                case 'mcma':
                    interactionType = 'choice';
                    var myScore = 0;
                    for(var j = 0; j< questions_answers[idQuestion].length;j++) {
                        var idAnswer = questions_answers[idQuestion][j];
                        var answer = document.getElementById('question_'+(idQuestion)+'_multiple_'+(idAnswer));
                        if (answer.checked) {
                            interactionAnswers += idAnswer+'__|';// changed by isaac flores
                            myScore += questions_answers_ponderation[idQuestion][idAnswer];
                        }
                    }
                    interactionScore = myScore;
                    scoreMax += questions_score_max[idQuestion];
                    addDebug("Score: "+myScore);
                    break;
                case 'mcua':
                    interactionType = 'choice';
                    var myScore = 0;
                    for (var j = 0; j<questions_answers[idQuestion].length; j++) {
                        var idAnswer = questions_answers[idQuestion][j];
                        var elementId = 'question_'+(idQuestion)+'_unique_'+(idAnswer);
                        var answer = document.getElementById(elementId);
                        if (answer.checked) {
                            addDebug('Element id # "'+ elementId +'" was checked');
                            interactionAnswers += idAnswer;
                            addDebug("List of correct answers: "+questions_answers_correct[idQuestion]);
                            addDebug('Score for this answer: ' + questions_answers_ponderation[idQuestion][idAnswer]);
                            addDebug("idAnswer: "+idAnswer);
                            addDebug("Option selected: "+questions_answers_correct[idQuestion][idAnswer]);
                            if (questions_answers_correct[idQuestion][idAnswer] == 1) {
                                if (questions_answers_ponderation[idQuestion][idAnswer]) {
                                    myScore += questions_answers_ponderation[idQuestion][idAnswer];
                                } else {
                                    myScore++;
                                }
                            }
                        }
                    }
                    addDebug("Score: "+myScore);
                    interactionScore = myScore;
                    scoreMax += questions_score_max[idQuestion];
                    break;
                case 'tf':
                    interactionType = 'true-false';
                    var myScore = 0;
                    for (var j = 0; j < questions_answers[idQuestion].length; j++) {
                        var idAnswer = questions_answers[idQuestion][j];
                        var answer = document.getElementById('question_' + idQuestion + '_tf_' + (idAnswer));
                        if (answer.checked.value) {
                            interactionAnswers += idAnswer;
                            for (k = 0; k < questions_answers_correct[idQuestion].length; k++) {
                                if (questions_answers_correct[idQuestion][k] == idAnswer) {
                                    if (questions_answers_ponderation[idQuestion][idAnswer]) {
                                        myScore += questions_answers_ponderation[idQuestion][idAnswer];
                                    } else {
                                        myScore++;
                                    }
                                }
                            }
                        }
                    }
                    addDebug("Score: "+  myScore);
                    interactionScore = myScore;
                    scoreMax += questions_score_max[idQuestion];
                    break;
                case 'fib':
                    interactionType = 'fill-in';
                    var myScore = 0;
                    for (var j = 0; j < questions_answers[idQuestion].length; j++) {
                        var idAnswer = questions_answers[idQuestion][j];
                        var answer = document.getElementById('question_'+(idQuestion)+'_fib_'+(idAnswer));
                        if (answer.value) {
                            interactionAnswers += answer.value + '__|';//changed by isaac flores
                            for (k = 0; k < questions_answers_correct[idQuestion].length; k++) {
                                if (questions_answers_correct[idQuestion][k] == answer.value) {
                                    if (questions_answers_ponderation[idQuestion][idAnswer]) {
                                        myScore += questions_answers_ponderation[idQuestion][idAnswer];
                                    } else {
                                        myScore++;
                                    }
                                }
                            }
                        }
                    }
                    addDebug("Score: "+myScore);
                    interactionScore = myScore;
                    scoreMax += questions_score_max[idQuestion];
                    break;
                case 'matching':
                    interactionType = 'matching';
                    var myScore = 0;
                    addDebug("List of correct answers: ");
                    console.log(questions_answers_correct[idQuestion]);
                    for (var j = 0; j < questions_answers[idQuestion].length; j++) {
                        var idAnswer = questions_answers[idQuestion][j];
                        var elementId = 'question_' + (idQuestion) + '_matching_' + (idAnswer);

                        addDebug("---------idAnswer #"+idAnswer+'------------------');
                        addDebug("Checking element #"+elementId);

                        var answer = document.getElementById(elementId);

                        if (answer && answer.value) {
                            interactionAnswers += answer.value + '__|';//changed by isaac flores
                            for (k = 0; k < questions_answers_correct[idQuestion].length; k++) {
                                var left = questions_answers_correct[idQuestion][k][0];
                                var right = questions_answers_correct[idQuestion][k][1];
                                addDebug('Left ' + left);
                                addDebug('Right ' + right);
                                addDebug('answer.value ' + answer.value);

                                if (right == idAnswer && left == answer.value) {
                                    addDebug('Score for this answer: ' + questions_answers_ponderation[idQuestion][idAnswer]);
                                    if (questions_answers_ponderation[idQuestion][idAnswer]) {
                                        myScore += questions_answers_ponderation[idQuestion][idAnswer];
                                    } else {
                                        // myScore++;
                                    }
                                }
                            }
                        }
                        addDebug("Partial score: "+myScore);
                        addDebug("--------- end --- idAnswer #"+idAnswer+'------------------');
                    }
                    addDebug("Score: "+myScore);
                    interactionScore = myScore;
                    scoreMax += questions_score_max[idQuestion];
                    break;
                case 'free':
                    //ignore for now as a score cannot be given
                    interactionType = 'free';
                    var answer = document.getElementById('question_'+(idQuestion)+'_free');
                    if (answer && answer.value) {
                        interactionAnswers += answer.value
                    }

                    //interactionScore = questions_score_max[idQuestion];
                    interactionScore = 0;
                    scoreMax += questions_score_max[idQuestion];

                    //interactionAnswers = document.getElementById('question_'+(idQuestion)+'_free').value;
                    //correct responses work by pattern, see SCORM Runtime Env Doc
                    //interactionCorrectResponses += questions_answers_correct[idQuestion].toString();
                    break;
                case 'hotspot':
                    interactionType = 'sequencing';
                    interactionScore = 0;
                    //if(question_score && question_score[idQuestion]){
                    //	interactionScore = question_score[idQuestion];
                    //} //else, 0
                    //interactionAnswers = document.getElementById('question_'+(idQuestion)+'_free').innerHTML;
                    //correct responses work by pattern, see SCORM Runtime Env Doc
                    //for(k=0;k<questions_answers_correct[idQuestion].length;k++)
                    //{
                    //	interactionCorrectResponses += questions_answers_correct[idQuestion][k].toString()+',';
                    //}
                    break;
                case 'exact':
                    interactionType = 'exact';
                    interactionScore = 0;
                    var real_answers = new Array();
                    for (var j = 0; j < questions_answers[idQuestion].length; j++) {
                        var idAnswer = questions_answers[idQuestion][j];
                        var answer = document.getElementById('question_' + (idQuestion) + '_exact_' + (idAnswer));

                        if (answer.checked == true) {
                            interactionAnswers += idAnswer+', ';
                            if (questions_answers_correct[idQuestion][idAnswer] != 0) {
                                real_answers[j] = true;
                            } else {
                                real_answers[j] = false;
                            }
                        } else {
                            if (questions_answers_correct[idQuestion][idAnswer] != 0) {
                                real_answers[j] = false;
                            } else {
                                real_answers[j] = true;
                            }
                        }
                    }

                    var final_answer = true;
                    for (var z = 0; z < real_answers.length; z++) {
                        if (real_answers[z] == false) {
                            final_answer = false;
                        }
                    }
                    interactionScore = 0;
                    addDebug(real_answers);
                    if (final_answer) {
                        //getting only the first score where we save the weight of all the question
                        interactionScore = questions_answers_ponderation[idQuestion][1];
                    }
                    addDebug("Score: "+interactionScore);
                    scoreMax += questions_score_max[idQuestion];
                    break;
            }
            tmpScore += interactionScore;
            doLMSSetValue('cmi.interactions.'+idQuestion+'.id', 'Q'+idQuestion);
            doLMSSetValue('cmi.interactions.'+idQuestion+'.type', interactionType);
            doLMSSetValue('cmi.interactions.'+idQuestion+'.student_response', interactionAnswers);
            doLMSSetValue('cmi.interactions.'+idQuestion+'.result', interactionScore);
        }
    }
    doLMSSetValue('cmi.core.score.min', 0);
    doLMSSetValue('cmi.core.score.max', scoreMax);
    doLMSSetValue('cmi.core.score.raw', tmpScore);

    // Get status
    var mastery_score = doLMSGetValue('cmi.student_data.mastery_score');
    if (mastery_score <= 0) {
        mastery_score = (scoreMax * 0.80);
    }
    if (tmpScore >= mastery_score) {
        status = 'passed';
    } else {
        status = 'failed';
    }

    addDebug('student_score: ' + tmpScore);
    addDebug('mastery_score: ' + mastery_score);
    addDebug('cmi.core.score.max: ' + scoreMax);
    addDebug('cmi.core.lesson_status: ' + status);
    doLMSSetValue('cmi.core.lesson_status', status);
    if (interrupted && (status != 'completed') && (status != 'passed')) {
        doLMSSetValue('cmi.core.exit', 'suspended');
    }

    return false; //do not submit the form
}

(function($){
    //Shuffle all rows, while keeping the first column
    //Requires: Shuffle
    $.fn.shuffleRows = function(){
        return this.each(function(){
            var main = $(/table/i.test(this.tagName) ? this.tBodies[0] : this);
            var firstElem = [], counter=0;
            main.children().each(function(){
                firstElem.push(this.firstChild);
            });
            main.shuffle();
            main.children().each(function(){
                this.insertBefore(firstElem[counter++], this.firstChild);
            });
        });
    }
    /* Shuffle is required */
    $.fn.shuffle = function() {
        return this.each(function(){
            var items = $(this).children();
            return (items.length)
                ? $(this).html($.shuffle(items))
                : this;
        });
    }

    $.shuffle = function(arr) {
        for(
            var j, x, i = arr.length; i;
            j = parseInt(Math.random() * i),
                x = arr[--i], arr[i] = arr[j], arr[j] = x
        );
        return arr;
    }
})(jQuery);

/*
* Assigns any event handler to any element
* @param	object	Element on which the event is added
* @param	string	Name of event
* @param	string	Function to trigger on event
* @param	boolean	Capture the event and prevent
*/
function addEvent(elm, evType, fn, useCapture)
{
    if (elm.addEventListener) {
        elm.addEventListener(evType, fn, useCapture);
        return true;
    } else if(elm.attachEvent) {
        var r = elm.attachEvent('on' + evType, fn);
        return r;
    } else {
        elm['on' + evType] = fn;
    }
}
