/*jslint browser: true, unparam: true */
/*global jQuery, ui, rulesEngine, defaultOptions, zxcvbn, console */

/*
* jQuery Password Strength plugin for Twitter Bootstrap
*
* Copyright (c) 2008-2013 Tane Piper
* Copyright (c) 2013 Alejandro Blanco
* Dual licensed under the MIT and GPL licenses.
*/

var methods = {};

(function ($, methods) {
    "use strict";
    var onKeyUp, onPaste, applyToAll;

    onKeyUp = function (event) {
        var $el = $(event.target),
            options = $el.data("pwstrength-bootstrap"),
            word = $el.val(),
            userInputs,
            verdictText,
            verdictLevel,
            score;

        if (options === undefined) { return; }

        options.instances.errors = [];
        if (word.length === 0) {
            score = undefined;
        } else {
            if (options.common.zxcvbn) {
                userInputs = [];
                $.each(options.common.userInputs.concat([options.common.usernameField]), function (idx, selector) {
                    var value = $(selector).val();
                    if (value) { userInputs.push(value); }
                });
                userInputs = userInputs.concat(options.common.zxcvbnTerms);
                score = zxcvbn(word, userInputs).guesses;
                score = Math.log(score) * Math.LOG2E;
            } else {
                score = rulesEngine.executeRules(options, word);
            }
            if ($.isFunction(options.common.onScore)) {
                score = options.common.onScore(options, word, score);
            }
        }
        ui.updateUI(options, $el, score);
        verdictText = ui.getVerdictAndCssClass(options, score);
        verdictLevel = verdictText[1];
        verdictText = verdictText[0];

        if (options.common.debug) {
            console.log(score + ' - ' + verdictText);
        }

        if ($.isFunction(options.common.onKeyUp)) {
            options.common.onKeyUp(event, {
                score: score,
                verdictText: verdictText,
                verdictLevel: verdictLevel
            });
        }
    };

    onPaste = function (event) {
        // This handler is necessary because the paste event fires before the
        // content is actually in the input, so we cannot read its value right
        // away. Therefore, the timeouts.
        var $el = $(event.target),
            word = $el.val(),
            tries = 0,
            callback;

        callback = function () {
            var newWord = $el.val();

            if (newWord !== word) {
                onKeyUp(event);
            } else if (tries < 3) {
                tries += 1;
                setTimeout(callback, 100);
            }
        };

        setTimeout(callback, 100);
    };

    methods.init = function (settings) {
        this.each(function (idx, el) {
            // Make it deep extend (first param) so it extends also the
            // rules and other inside objects
            var clonedDefaults = $.extend(true, {}, defaultOptions),
                localOptions = $.extend(true, clonedDefaults, settings),
                $el = $(el);

            localOptions.instances = {};
            $el.data("pwstrength-bootstrap", localOptions);

            $.each(localOptions.common.events, function (idx, eventName) {
                var handler = eventName === "paste" ? onPaste : onKeyUp;
                $el.on(eventName, handler);
            });

            ui.initUI(localOptions, $el);
            $el.trigger("keyup");

            if ($.isFunction(localOptions.common.onLoad)) {
                localOptions.common.onLoad();
            }
        });

        return this;
    };

    methods.destroy = function () {
        this.each(function (idx, el) {
            var $el = $(el),
                options = $el.data("pwstrength-bootstrap"),
                elements = ui.getUIElements(options, $el);
            elements.$progressbar.remove();
            elements.$verdict.remove();
            elements.$errors.remove();
            $el.removeData("pwstrength-bootstrap");
        });
    };

    methods.forceUpdate = function () {
        this.each(function (idx, el) {
            var event = { target: el };
            onKeyUp(event);
        });
    };

    methods.addRule = function (name, method, score, active) {
        this.each(function (idx, el) {
            var options = $(el).data("pwstrength-bootstrap");

            options.rules.activated[name] = active;
            options.rules.scores[name] = score;
            options.rules.extra[name] = method;
        });
    };

    applyToAll = function (rule, prop, value) {
        this.each(function (idx, el) {
            $(el).data("pwstrength-bootstrap").rules[prop][rule] = value;
        });
    };

    methods.changeScore = function (rule, score) {
        applyToAll.call(this, rule, "scores", score);
    };

    methods.ruleActive = function (rule, active) {
        applyToAll.call(this, rule, "activated", active);
    };

    methods.ruleIsMet = function (rule) {
        if ($.isFunction(rulesEngine.validation[rule])) {
            if (rule === "wordMinLength") {
                rule = "wordMinLengthStaticScore";
            } else if (rule === "wordMaxLength") {
                rule = "wordMaxLengthStaticScore";
            }

            var rulesMetCnt = 0;

            this.each(function (idx, el) {
                var options = $(el).data("pwstrength-bootstrap");

                rulesMetCnt += rulesEngine.validation[rule](options, $(el).val(), 1);
            });

            return (rulesMetCnt === this.length);
        }

        $.error("Rule " + rule + " does not exist on jQuery.pwstrength-bootstrap.validation");
    };

    $.fn.pwstrength = function (method) {
        var result;

        if (methods[method]) {
            result = methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === "object" || !method) {
            result = methods.init.apply(this, arguments);
        } else {
            $.error("Method " +  method + " does not exist on jQuery.pwstrength-bootstrap");
        }

        return result;
    };
}(jQuery, methods));
