/*jslint browser: true */
/*global */

/*
* jQuery Password Strength plugin for Twitter Bootstrap
*
* Copyright (c) 2008-2013 Tane Piper
* Copyright (c) 2013 Alejandro Blanco
* Dual licensed under the MIT and GPL licenses.
*/

var i18n = {};

(function (i18n, i18next) {
    'use strict';

    i18n.fallback = {
        "wordLength": "Your password is too short",
        "wordNotEmail": "Do not use your email as your password",
        "wordSimilarToUsername": "Your password cannot contain your username",
        "wordTwoCharacterClasses": "Use different character classes",
        "wordRepetitions": "Too many repetitions",
        "wordSequences": "Your password contains sequences",
        "errorList": "Errors:",
        "veryWeak": "Very Weak",
        "weak": "Weak",
        "normal": "Normal",
        "medium": "Medium",
        "strong": "Strong",
        "veryStrong": "Very Strong"
    };

    i18n.t = function (key) {
        var result = '';

        // Try to use i18next.com
        if (i18next) {
            result = i18next.t(key);
        } else {
            // Fallback to english
            result = i18n.fallback[key];
        }

        return result === key ? '' : result;
    };
}(i18n, window.i18next));
