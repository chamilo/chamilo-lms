/**
 * @param {function} callback
 * @param {string} target
 * @param {Array} resources
 * @constructor
 */
$.frameReady = function (callback, target, resources) {
    /**
     * @type {window}
     */
    var targetWindow = eval(target);
    /**
     * @type {Document}
     */
    var targetDocument = null;

    var scripts = resources.filter(function (resource) {
        return resource.type === 'script';
    });
    var stylesheets = resources.filter(function (resource) {
        return resource.type === 'stylesheet';
    });

    var scriptsCount = (function () {
        var count = 0;

        /**
         * @param {Object} parentScript
         */
        function countScripts(parentScript) {
            count++;

            if (!parentScript.hasOwnProperty('deps')) {
                return;
            }

            parentScript.deps.forEach(countScripts);
        }

        scripts.forEach(countScripts);

        return count;
    })();

    targetWindow.onload = function () {
        targetDocument = targetWindow.document;

        scripts.forEach(function (script) {
            createScript(script);
        });

        stylesheets.forEach(function (stylesheet) {
            createStylesheet(stylesheet);
        });
    };

    var scripsLoadedCount = 0;

    /**
     * @param {Object} script
     */
    function createScript(script) {
        /**
         * @type {HTMLScriptElement}
         */
        var elParent = targetWindow.document.createElement('script');
        elParent.async = false;
        elParent.onload = function () {
            scripsLoadedCount++;

            if (!script.hasOwnProperty('deps')) {
                tryExecuteCallback();

                return;
            }

            script.deps.forEach(function (scriptB) {
                createScript(scriptB);
            });
        };
        elParent.setAttribute('src', script.src);

        targetDocument.body.appendChild(elParent);
    }

    /**
     * @param {Object} stylesheet
     */
    function createStylesheet(stylesheet) {
        /**
         * @type {HTMLLinkElement}
         */
        var el = targetWindow.document.createElement('link');
        el.setAttribute('href', stylesheet.src);
        el.setAttribute('rel', "stylesheet");
        el.setAttribute('type', "text/css");

        targetDocument.head.appendChild(el);
    }

    function tryExecuteCallback() {
        if (scripsLoadedCount < scriptsCount) {
            return;
        }

        targetWindow.eval('(' + callback.toString() + ')();');
    }
};
