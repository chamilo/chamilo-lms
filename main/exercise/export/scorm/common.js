var questions = new Array();
var questions_answers = new Array();
var questions_answers_correct = new Array();
var questions_types = new Array();
var questions_score_max = new Array();
var questions_answers_ponderation = new Array();

/**
 * Adds the event listener
 */
function addListeners(e) {
    loadPage();
    var myButton = document.getElementById('chamilo_scorm_submit');
    addEvent(myButton, 'click', doQuit, false);
    addEvent(myButton, 'click', disableButton, false);
    addEvent(window, 'unload', unloadPage, false);
}

/** Disables the submit button on SCORM result submission **/
function disableButton() {
    var mybtn = document.getElementById('chamilo_scorm_submit');
    mybtn.setAttribute('disabled', 'disabled');
}

addEvent(window,'load', addListeners, false);