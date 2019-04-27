/*jslint browser: true, unparam: true */
/*global jQuery, i18n */

/*
* jQuery Password Strength plugin for Twitter Bootstrap
*
* Copyright (c) 2008-2013 Tane Piper
* Copyright (c) 2013 Alejandro Blanco
* Dual licensed under the MIT and GPL licenses.
*/

var defaultOptions = {};

defaultOptions.common = {};
defaultOptions.common.minChar = 6;
defaultOptions.common.usernameField = "#username";
defaultOptions.common.userInputs = [
    // Selectors for input fields with user input
];
defaultOptions.common.onLoad = undefined;
defaultOptions.common.onKeyUp = undefined;
defaultOptions.common.onScore = undefined;
defaultOptions.common.zxcvbn = false;
defaultOptions.common.zxcvbnTerms = [
    // List of disrecommended words
];
defaultOptions.common.events = ["keyup", "change", "paste"];
defaultOptions.common.debug = false;

defaultOptions.rules = {};
defaultOptions.rules.extra = {};
defaultOptions.rules.scores = {
    wordNotEmail: -100,
    wordLength: -50,
    wordSimilarToUsername: -100,
    wordSequences: -20,
    wordTwoCharacterClasses: 2,
    wordRepetitions: -25,
    wordLowercase: 1,
    wordUppercase: 3,
    wordOneNumber: 3,
    wordThreeNumbers: 5,
    wordOneSpecialChar: 3,
    wordTwoSpecialChar: 5,
    wordUpperLowerCombo: 2,
    wordLetterNumberCombo: 2,
    wordLetterNumberCharCombo: 2
};
defaultOptions.rules.activated = {
    wordNotEmail: true,
    wordLength: true,
    wordSimilarToUsername: true,
    wordSequences: true,
    wordTwoCharacterClasses: false,
    wordRepetitions: false,
    wordLowercase: true,
    wordUppercase: true,
    wordOneNumber: true,
    wordThreeNumbers: true,
    wordOneSpecialChar: true,
    wordTwoSpecialChar: true,
    wordUpperLowerCombo: true,
    wordLetterNumberCombo: true,
    wordLetterNumberCharCombo: true
};
defaultOptions.rules.raisePower = 1.4;

defaultOptions.ui = {};
defaultOptions.ui.bootstrap2 = false;
defaultOptions.ui.bootstrap4 = false;
defaultOptions.ui.colorClasses = [
    "danger", "danger", "danger", "warning", "warning", "success"
];
defaultOptions.ui.showProgressBar = true;
defaultOptions.ui.progressBarEmptyPercentage = 1;
defaultOptions.ui.progressBarMinPercentage = 1;
defaultOptions.ui.progressExtraCssClasses = '';
defaultOptions.ui.progressBarExtraCssClasses = '';
defaultOptions.ui.showPopover = false;
defaultOptions.ui.popoverPlacement = "bottom";
defaultOptions.ui.showStatus = false;
defaultOptions.ui.spanError = function (options, key) {
    "use strict";
    var text = options.i18n.t(key);
    if (!text) { return ''; }
    return '<span style="color: #d52929">' + text + '</span>';
};
defaultOptions.ui.popoverError = function (options) {
    "use strict";
    var errors = options.instances.errors,
        errorsTitle = options.i18n.t("errorList"),
        message = "<div>" + errorsTitle + "<ul class='error-list' style='margin-bottom: 0;'>";

    jQuery.each(errors, function (idx, err) {
        message += "<li>" + err + "</li>";
    });
    message += "</ul></div>";
    return message;
};
defaultOptions.ui.showVerdicts = true;
defaultOptions.ui.showVerdictsInsideProgressBar = false;
defaultOptions.ui.useVerdictCssClass = false;
defaultOptions.ui.showErrors = false;
defaultOptions.ui.showScore = false;
defaultOptions.ui.container = undefined;
defaultOptions.ui.viewports = {
    progress: undefined,
    verdict: undefined,
    errors: undefined,
    score: undefined
};
defaultOptions.ui.scores = [0, 14, 26, 38, 50];

defaultOptions.i18n = {};
defaultOptions.i18n.t = i18n.t;
