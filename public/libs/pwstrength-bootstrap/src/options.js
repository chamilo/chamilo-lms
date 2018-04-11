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
defaultOptions.common.maxChar = 20;
defaultOptions.common.usernameField = "#username";
defaultOptions.common.invalidCharsRegExp = new RegExp(/[\s,'"]/);
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
    wordMinLength: -50,
    wordMaxLength: -50,
    wordInvalidChar: -100,
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
    wordLetterNumberCharCombo: 2,
    wordIsACommonPassword: -100
};
defaultOptions.rules.activated = {
    wordNotEmail: true,
    wordMinLength: true,
    wordMaxLength: false,
    wordInvalidChar: false,
    wordSimilarToUsername: true,
    wordSequences: true,
    wordTwoCharacterClasses: true,
    wordRepetitions: true,
    wordLowercase: true,
    wordUppercase: true,
    wordOneNumber: true,
    wordThreeNumbers: true,
    wordOneSpecialChar: true,
    wordTwoSpecialChar: true,
    wordUpperLowerCombo: true,
    wordLetterNumberCombo: true,
    wordLetterNumberCharCombo: true,
    wordIsACommonPassword: true
};
defaultOptions.rules.raisePower = 1.4;
// List taken from https://github.com/danielmiessler/SecLists (MIT License)
defaultOptions.rules.commonPasswords = [
    '123456',
    'password',
    '12345678',
    'qwerty',
    '123456789',
    '12345',
    '1234',
    '111111',
    '1234567',
    'dragon',
    '123123',
    'baseball',
    'abc123',
    'football',
    'monkey',
    'letmein',
    '696969',
    'shadow',
    'master',
    '666666',
    'qwertyuiop',
    '123321',
    'mustang',
    '1234567890',
    'michael',
    '654321',
    'pussy',
    'superman',
    '1qaz2wsx',
    '7777777',
    'fuckyou',
    '121212',
    '000000',
    'qazwsx',
    '123qwe',
    'killer',
    'trustno1',
    'jordan',
    'jennifer',
    'zxcvbnm',
    'asdfgh',
    'hunter',
    'buster',
    'soccer',
    'harley',
    'batman',
    'andrew',
    'tigger',
    'sunshine',
    'iloveyou',
    'fuckme',
    '2000',
    'charlie',
    'robert',
    'thomas',
    'hockey',
    'ranger',
    'daniel',
    'starwars',
    'klaster',
    '112233',
    'george',
    'asshole',
    'computer',
    'michelle',
    'jessica',
    'pepper',
    '1111',
    'zxcvbn',
    '555555',
    '11111111',
    '131313',
    'freedom',
    '777777',
    'pass',
    'fuck',
    'maggie',
    '159753',
    'aaaaaa',
    'ginger',
    'princess',
    'joshua',
    'cheese',
    'amanda',
    'summer',
    'love',
    'ashley',
    '6969',
    'nicole',
    'chelsea',
    'biteme',
    'matthew',
    'access',
    'yankees',
    '987654321',
    'dallas',
    'austin',
    'thunder',
    'taylor',
    'matrix'
];

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
